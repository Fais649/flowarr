import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';

type MetricCardProps = {
    label: string;
    value: string | number;
    trend?: {
        value: string;
        positive: boolean;
    };
};

export function MetricCard({ label, value, trend }: MetricCardProps) {
    return (
        <Card>
            <CardHeader className="pb-2">
                <CardTitle className="text-sm font-medium text-muted-foreground">
                    {label}
                </CardTitle>
            </CardHeader>
            <CardContent>
                <div className="flex items-baseline gap-2">
                    <span className="text-3xl font-bold">{value}</span>
                    {trend && (
                        <span
                            className={
                                trend.positive
                                    ? 'text-sm text-green-600'
                                    : 'text-sm text-red-600'
                            }
                        >
                            {trend.value}
                        </span>
                    )}
                </div>
            </CardContent>
        </Card>
    );
}
