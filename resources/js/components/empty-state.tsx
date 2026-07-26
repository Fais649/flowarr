import { Button } from '@/components/ui/button';
import {
    Card,
    CardContent,
    CardDescription,
    CardFooter,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';

type EmptyStateProps = {
    title: string;
    description: string;
    action?: {
        label: string;
        onClick: () => void;
    };
};

export function EmptyState({ title, description, action }: EmptyStateProps) {
    return (
        <Card className="mx-auto mt-12 max-w-md">
            <CardHeader>
                <CardTitle className="text-center">{title}</CardTitle>
                <CardDescription className="text-center">
                    {description}
                </CardDescription>
            </CardHeader>
            {action && (
                <CardFooter className="justify-center">
                    <Button onClick={action.onClick}>{action.label}</Button>
                </CardFooter>
            )}
        </Card>
    );
}
