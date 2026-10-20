import { router } from '@inertiajs/react';
import { useState } from 'react';
import LoginLinkController from '@/actions/App/Http/Controllers/Auth/LoginLinkController';
import { Button } from '@/components/ui/button';
import { Separator } from '@/components/ui/separator';
import { Spinner } from '@/components/ui/spinner';

export type LoginLinkUser = {
    email: string;
    role: string | null;
    team: string | null;
};

type Props = {
    users: LoginLinkUser[];
};

export default function LoginLink({ users }: Props) {
    const [processingEmail, setProcessingEmail] = useState<string | null>(null);

    if (users.length === 0) {
        return null;
    }

    const logIn = (email: string) => {
        router.post(
            LoginLinkController(),
            { email },
            {
                onStart: () => setProcessingEmail(email),
                onFinish: () => setProcessingEmail(null),
            },
        );
    };

    return (
        <>
            <div className="relative my-6">
                <div className="absolute inset-0 flex items-center">
                    <Separator className="w-full" />
                </div>
                <div className="relative flex justify-center text-xs uppercase">
                    <span className="bg-background px-2 text-muted-foreground">
                        Local login
                    </span>
                </div>
            </div>

            <div className="grid gap-2">
                {users.map((user) => (
                    <Button
                        key={user.email}
                        type="button"
                        variant="outline"
                        className="w-full"
                        onClick={() => logIn(user.email)}
                        disabled={processingEmail !== null}
                        data-test="login-link-button"
                    >
                        {processingEmail === user.email && <Spinner />}
                        {user.role && user.team
                            ? `Log in as ${user.role} of ${user.team}`
                            : `Log in as ${user.email}`}
                    </Button>
                ))}
            </div>
        </>
    );
}
