import { Input } from '@/components/ui/input';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';

type FilterOption = {
    value: string;
    label: string;
};

type FilterBarProps = {
    filters: {
        key: string;
        label: string;
        value: string;
        options: FilterOption[];
    }[];
    search?: {
        value: string;
        placeholder: string;
        onChange: (value: string) => void;
    };
};

export function FilterBar({ filters, search }: FilterBarProps) {
    return (
        <div className="flex flex-wrap items-center gap-3">
            {filters.map((filter) => (
                <div key={filter.key} className="flex items-center gap-2">
                    <span className="text-sm font-medium text-muted-foreground">
                        {filter.label}
                    </span>
                    <Select
                        value={filter.value}
                        onValueChange={(value) => {
                            const params = new URLSearchParams(
                                window.location.search,
                            );

                            if (value && value !== 'all') {
                                params.set(filter.key, value);
                            } else {
                                params.delete(filter.key);
                            }

                            params.delete('page');
                            window.location.href = `${window.location.pathname}?${params.toString()}`;
                        }}
                    >
                        <SelectTrigger className="w-40">
                            <SelectValue />
                        </SelectTrigger>
                        <SelectContent>
                            {filter.options.map((opt) => (
                                <SelectItem key={opt.value} value={opt.value}>
                                    {opt.label}
                                </SelectItem>
                            ))}
                        </SelectContent>
                    </Select>
                </div>
            ))}
            {search && (
                <Input
                    placeholder={search.placeholder}
                    value={search.value}
                    onChange={(e) => search.onChange(e.target.value)}
                    className="w-60"
                />
            )}
        </div>
    );
}
