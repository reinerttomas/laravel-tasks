import type { Components } from 'react-markdown';
import ReactMarkdown from 'react-markdown';
import remarkGfm from 'remark-gfm';
import { cn } from '@/lib/utils';

// Styled per element because the typography plugin is not a project dependency.
const components: Components = {
    h1: ({ node: _node, ...props }) => (
        <h1 className="mt-6 mb-3 text-xl font-semibold first:mt-0" {...props} />
    ),
    h2: ({ node: _node, ...props }) => (
        <h2 className="mt-6 mb-3 text-lg font-semibold first:mt-0" {...props} />
    ),
    h3: ({ node: _node, ...props }) => (
        <h3 className="mt-4 mb-2 font-semibold first:mt-0" {...props} />
    ),
    p: ({ node: _node, ...props }) => (
        <p className="my-3 first:mt-0 last:mb-0" {...props} />
    ),
    a: ({ node: _node, ...props }) => (
        <a
            className="font-medium text-primary underline underline-offset-4"
            target="_blank"
            rel="noopener noreferrer"
            {...props}
        />
    ),
    ul: ({ node: _node, className, ...props }) => (
        <ul
            className={cn(
                'my-3 list-disc pl-6',
                className?.includes('contains-task-list') && 'list-none pl-0',
            )}
            {...props}
        />
    ),
    ol: ({ node: _node, ...props }) => (
        <ol className="my-3 list-decimal pl-6" {...props} />
    ),
    li: ({ node: _node, ...props }) => (
        <li className="my-1 [&>input]:mr-2 [&>input]:align-middle" {...props} />
    ),
    blockquote: ({ node: _node, ...props }) => (
        <blockquote
            className="my-3 border-l-2 pl-4 text-muted-foreground"
            {...props}
        />
    ),
    code: ({ node: _node, className, ...props }) => (
        <code
            className={cn(
                'rounded bg-muted px-1 py-0.5 font-mono text-[0.9em]',
                className,
            )}
            {...props}
        />
    ),
    pre: ({ node: _node, ...props }) => (
        <pre
            className="my-3 overflow-x-auto rounded-md bg-muted p-3 text-xs [&>code]:bg-transparent [&>code]:p-0"
            {...props}
        />
    ),
    hr: ({ node: _node, ...props }) => <hr className="my-6" {...props} />,
    table: ({ node: _node, ...props }) => (
        <div className="my-3 overflow-x-auto">
            <table className="w-full border-collapse text-left" {...props} />
        </div>
    ),
    th: ({ node: _node, ...props }) => (
        <th className="border-b px-3 py-1.5 font-medium" {...props} />
    ),
    td: ({ node: _node, ...props }) => (
        <td className="border-b px-3 py-1.5" {...props} />
    ),
};

/** Renders user-written markdown (GFM); raw HTML is dropped, never rendered. */
export default function Markdown({
    children,
    className,
}: {
    children: string;
    className?: string;
}) {
    return (
        <div
            className={cn('text-sm leading-relaxed break-words', className)}
            data-test="markdown"
        >
            <ReactMarkdown
                remarkPlugins={[remarkGfm]}
                components={components}
                skipHtml
            >
                {children}
            </ReactMarkdown>
        </div>
    );
}
