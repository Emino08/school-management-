import React from 'react';
import { Button } from '@/components/ui/button';
import { cn } from '@/lib/utils';

export const RedButton = ({ children, className, ...props }) => (
  <Button
    className={cn('bg-red-600 hover:bg-red-500 text-white ml-1', className)}
    {...props}
  >
    {children}
  </Button>
);

export const BlackButton = ({ children, className, ...props }) => (
  <Button
    className={cn('bg-black hover:bg-gray-800 text-white ml-1', className)}
    {...props}
  >
    {children}
  </Button>
);

export const DarkRedButton = ({ children, className, ...props }) => (
  <Button
    className={cn('bg-red-900 hover:bg-red-500 text-white', className)}
    {...props}
  >
    {children}
  </Button>
);

export const BlueButton = ({ children, className, ...props }) => (
  <Button
    className={cn('bg-blue-950 hover:bg-blue-800 text-white', className)}
    {...props}
  >
    {children}
  </Button>
);

export const PurpleButton = ({ children, className, ...props }) => (
  <Button
    className={cn('bg-purple-950 hover:bg-purple-800 text-white', className)}
    {...props}
  >
    {children}
  </Button>
);

export const LightPurpleButton = ({ children, className, ...props }) => (
  <Button
    className={cn('bg-purple-600 hover:bg-purple-700 text-white', className)}
    {...props}
  >
    {children}
  </Button>
);

export const GreenButton = ({ children, className, ...props }) => (
  <Button
    className={cn('bg-green-900 hover:bg-green-700 text-white', className)}
    {...props}
  >
    {children}
  </Button>
);

export const BrownButton = ({ children, className, ...props }) => (
  <Button
    className={cn('bg-amber-950 hover:bg-amber-900 text-white', className)}
    {...props}
  >
    {children}
  </Button>
);

export const IndigoButton = ({ children, className, ...props }) => (
  <Button
    className={cn('bg-indigo-800 hover:bg-indigo-700 text-white', className)}
    {...props}
  >
    {children}
  </Button>
);
