#!/usr/bin/env bash
#
# Starts a deploy of the Dokploy compose app and waits until Dokploy reports its
# result. The compose stack runs the `latest` image and pulls it on every deploy,
# so the app's env is never read or written here.
#
# Usage: .infrastructure/dokploy-deploy.sh
#
# Environment:
#   DOKPLOY_URL             Dokploy base URL, e.g. https://dokploy.example.com
#   DOKPLOY_API_KEY         Dokploy API key
#   DOKPLOY_COMPOSE_ID      ID of the compose app
#   DOKPLOY_DEPLOY_TITLE    Title of the deployment in Dokploy (default "Deploy latest")
#   DOKPLOY_DEPLOY_TIMEOUT  Seconds to wait for the deploy result (default 600)
#   DOKPLOY_POLL_INTERVAL   Seconds between status checks (default 5)
#
# Exit codes: 0 deployed, 1 deploy failed or timed out, 64 usage or configuration
# error, 69 curl or jq missing. Configuration is checked before any API call.

set -euo pipefail

if [[ $# -ne 0 ]]; then
    echo "Usage: $0 (configured through the environment only)" >&2
    exit 64
fi

missing=()
for name in DOKPLOY_URL DOKPLOY_API_KEY DOKPLOY_COMPOSE_ID; do
    if [[ -z ${!name:-} ]]; then
        missing+=("$name")
    fi
done
if [[ ${#missing[@]} -gt 0 ]]; then
    echo "Missing environment variables: ${missing[*]}" >&2
    exit 64
fi

timeout=${DOKPLOY_DEPLOY_TIMEOUT:-600}
interval=${DOKPLOY_POLL_INTERVAL:-5}
if [[ ! $timeout =~ ^[1-9][0-9]*$ || ! $interval =~ ^[1-9][0-9]*$ ]]; then
    echo "DOKPLOY_DEPLOY_TIMEOUT and DOKPLOY_POLL_INTERVAL must be positive whole seconds." >&2
    exit 64
fi

for tool in curl jq; do
    if ! command -v "$tool" > /dev/null; then
        echo "$tool is required." >&2
        exit 69
    fi
done

base_url=${DOKPLOY_URL%/}/api
title=${DOKPLOY_DEPLOY_TITLE:-Deploy latest}

# The API key goes in through a file descriptor, so it never shows in the
# process list.
api() {
    curl --fail --silent --show-error --max-time 30 \
        --header @<(printf 'x-api-key: %s\n' "$DOKPLOY_API_KEY") \
        --header 'Accept: application/json' \
        "$@"
}

api_get() {
    api --get --data-urlencode "composeId=$DOKPLOY_COMPOSE_ID" "$base_url/$1"
}

# Sends stdin as the JSON body.
api_post() {
    api --header 'Content-Type: application/json' --data-binary @- "$base_url/$1"
}

# compose.deploy only queues the deploy and returns no ID, so the new deployment
# is the one with this title that was not there before.
known_deployment_ids=$(api_get deployment.allByCompose | jq --compact-output 'map(.deploymentId)')

echo "Starting Dokploy deploy \"$title\""
jq --null-input --arg composeId "$DOKPLOY_COMPOSE_ID" --arg title "$title" '{composeId: $composeId, title: $title}' \
    | api_post compose.deploy > /dev/null

# shellcheck disable=SC2016 # $title and $known are jq variables.
find_new_deployment='
    map(select(.title == $title and (.deploymentId | IN($known[]) | not)))
    | sort_by(.createdAt)
    | last
    | if . then "\(.deploymentId) \(.status // "running")" else empty end
'

deadline=$((SECONDS + timeout))
deployment_id=
status=
reported_status=

while true; do
    if ((SECONDS >= deadline)); then
        status=timeout
        break
    fi

    sleep "$interval"

    # A failed status check is retried until the deadline, since the deploy is
    # already running and only its result is missing.
    if ! deployments=$(api_get deployment.allByCompose); then
        echo "Could not read the deployments, retrying" >&2
        continue
    fi

    new_deployment=$(jq --raw-output --argjson known "$known_deployment_ids" --arg title "$title" "$find_new_deployment" <<< "$deployments")
    if [[ -z $new_deployment ]]; then
        continue
    fi

    read -r deployment_id status <<< "$new_deployment"

    if [[ $status != "$reported_status" ]]; then
        echo "Deployment $deployment_id: $status"
        reported_status=$status
    fi

    case $status in
        done | error | cancelled) break ;;
    esac
done

if [[ -n ${GITHUB_OUTPUT:-} ]]; then
    {
        echo "deployment_id=$deployment_id"
        echo "status=$status"
    } >> "$GITHUB_OUTPUT"
fi

case $status in
    done)
        echo "Deployed \"$title\"."
        ;;
    timeout)
        echo "No result from Dokploy within ${timeout}s. Check the deployment in Dokploy." >&2
        exit 1
        ;;
    *)
        echo "Dokploy deployment ended with status \"$status\". Its log is in Dokploy." >&2
        exit 1
        ;;
esac
