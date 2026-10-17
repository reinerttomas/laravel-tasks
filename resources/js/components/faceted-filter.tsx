import type { LucideIcon } from 'lucide-react';
import { Check, PlusCircle } from 'lucide-react';
import type { ReactNode } from 'react';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
    Command,
    CommandEmpty,
    CommandGroup,
    CommandInput,
    CommandItem,
    CommandList,
    CommandSeparator,
} from '@/components/ui/command';
import {
    Popover,
    PopoverContent,
    PopoverTrigger,
} from '@/components/ui/popover';
import { Separator } from '@/components/ui/separator';
import { cn } from '@/lib/utils';

export type FacetOption = {
    value: string;
    label: string;
    icon?: LucideIcon;
    iconClassName?: string;
    /** Rendered instead of the icon, e.g. an avatar or a project colour swatch. */
    prefix?: ReactNode;
};

type Props = {
    title: string;
    options: FacetOption[];
    selected: string[];
    counts?: Record<string, number>;
    onChange: (values: string[]) => void;
};

export default function FacetedFilter({
    title,
    options,
    selected,
    counts = {},
    onChange,
}: Props) {
    const toggle = (value: string) =>
        onChange(
            selected.includes(value)
                ? selected.filter((item) => item !== value)
                : [...selected, value],
        );

    return (
        <Popover>
            <PopoverTrigger asChild>
                <Button
                    variant="outline"
                    size="sm"
                    className="h-8 border-dashed"
                    data-test={`facet-${title.toLowerCase()}`}
                >
                    <PlusCircle />
                    {title}
                    {selected.length > 0 && (
                        <>
                            <Separator
                                orientation="vertical"
                                className="mx-1 data-[orientation=vertical]:h-4"
                            />
                            <Badge
                                variant="secondary"
                                className="rounded-sm px-1 font-normal lg:hidden"
                            >
                                {selected.length}
                            </Badge>
                            <div className="hidden gap-1 lg:flex">
                                {selected.length > 2 ? (
                                    <Badge
                                        variant="secondary"
                                        className="rounded-sm px-1 font-normal"
                                    >
                                        {selected.length} selected
                                    </Badge>
                                ) : (
                                    options
                                        .filter((option) =>
                                            selected.includes(option.value),
                                        )
                                        .map((option) => (
                                            <Badge
                                                key={option.value}
                                                variant="secondary"
                                                className="rounded-sm px-1 font-normal"
                                            >
                                                {option.label}
                                            </Badge>
                                        ))
                                )}
                            </div>
                        </>
                    )}
                </Button>
            </PopoverTrigger>
            <PopoverContent className="w-56 p-0" align="start">
                <Command>
                    <CommandInput placeholder={title} />
                    <CommandList>
                        <CommandEmpty>No results found.</CommandEmpty>
                        <CommandGroup>
                            {options.map((option) => {
                                const isSelected = selected.includes(
                                    option.value,
                                );
                                const Icon = option.icon;

                                return (
                                    <CommandItem
                                        key={option.value}
                                        value={option.value}
                                        keywords={[option.label]}
                                        data-test={`facet-option-${option.value}`}
                                        onSelect={() => toggle(option.value)}
                                    >
                                        <div
                                            className={cn(
                                                'flex size-4 items-center justify-center rounded-[4px] border',
                                                isSelected
                                                    ? 'border-primary bg-primary text-primary-foreground'
                                                    : 'border-input [&_svg]:invisible',
                                            )}
                                        >
                                            <Check className="size-3.5 text-primary-foreground" />
                                        </div>
                                        {option.prefix ??
                                            (Icon && (
                                                <Icon
                                                    className={cn(
                                                        'size-4 text-muted-foreground',
                                                        option.iconClassName,
                                                    )}
                                                />
                                            ))}
                                        <span className="truncate">
                                            {option.label}
                                        </span>
                                        <span className="ml-auto font-mono text-xs text-muted-foreground">
                                            {counts[option.value] ?? 0}
                                        </span>
                                    </CommandItem>
                                );
                            })}
                        </CommandGroup>
                        {selected.length > 0 && (
                            <>
                                <CommandSeparator />
                                <CommandGroup>
                                    <CommandItem
                                        onSelect={() => onChange([])}
                                        className="justify-center text-center"
                                    >
                                        Clear filters
                                    </CommandItem>
                                </CommandGroup>
                            </>
                        )}
                    </CommandList>
                </Command>
            </PopoverContent>
        </Popover>
    );
}
