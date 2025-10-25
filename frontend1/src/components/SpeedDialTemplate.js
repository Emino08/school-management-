import React from 'react';
import { Button } from '@/components/ui/button';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import { FiSliders } from 'react-icons/fi';

const SpeedDialTemplate = ({ actions }) => {
    return (
        <div className="fixed bottom-4 right-4 z-50">
            <DropdownMenu>
                <DropdownMenuTrigger asChild>
                    <Button
                        size="icon"
                        className="h-14 w-14 rounded-full bg-green-900 hover:bg-green-700 shadow-lg"
                    >
                        <FiSliders className="h-6 w-6" />
                    </Button>
                </DropdownMenuTrigger>
                <DropdownMenuContent align="end" className="w-48">
                    {actions.map((action) => (
                        <DropdownMenuItem
                            key={action.name}
                            onClick={action.action}
                            className="cursor-pointer"
                        >
                            <span className="mr-2">{action.icon}</span>
                            {action.name}
                        </DropdownMenuItem>
                    ))}
                </DropdownMenuContent>
            </DropdownMenu>
        </div>
    );
}

export default SpeedDialTemplate;