# MUI to shadcn/ui Migration Guide

## Overview
This document provides a comprehensive guide for migrating from Material-UI (MUI) to shadcn/ui components with react-icons.

## Completed Migrations

### Core Components
- ✅ AccountMenu.js
- ✅ buttonStyles.js
- ✅ Modal.js
- ✅ Popup.js
- ✅ SpeedDialTemplate.js
- ✅ TableTemplate.js
- ✅ TableViewTemplate.js
- ✅ SeeNotice.js
- ✅ styles.js
- ✅ LoginPage.js
- ✅ SideBar.js

### Package Changes
- ✅ Removed: @mui/material, @mui/icons-material, @mui/lab, @emotion/react, @emotion/styled, styled-components
- ✅ Added: react-icons, @radix-ui/react-checkbox, @radix-ui/react-toast

## Component Mapping Reference

### MUI → shadcn/ui

| MUI Component | shadcn/ui Component | Import Path |
|---------------|---------------------|-------------|
| Button | Button | @/components/ui/button |
| TextField | Input | @/components/ui/input |
| Paper | Card | @/components/ui/card |
| Menu | DropdownMenu | @/components/ui/dropdown-menu |
| Dialog | Dialog | @/components/ui/dialog |
| Checkbox | Checkbox | @/components/ui/checkbox |
| Select | Select | @/components/ui/select |
| Tabs | Tabs | @/components/ui/tabs |
| Avatar | Avatar | @/components/ui/avatar |
| Snackbar | Toast (useToast hook) | @/hooks/use-toast |
| Table | Table | @/components/ui/table |
| Divider | Separator | @/components/ui/separator |
| Alert | Alert | @/components/ui/alert |
| Badge | Badge | @/components/ui/badge |
| CircularProgress | Loader from react-icons | react-icons/fi |
| IconButton | Button with variant="ghost" size="icon" | @/components/ui/button |

### MUI Icons → React Icons

| MUI Icon | React Icon | Import |
|----------|------------|--------|
| Home | FiHome | react-icons/fi |
| PersonOutline | FiUsers | react-icons/fi |
| ExitToApp | FiLogOut | react-icons/fi |
| AccountCircleOutlined | FiUser | react-icons/fi |
| AnnouncementOutlined | FiBell | react-icons/fi |
| ClassOutlined | FiBook | react-icons/fi |
| SupervisorAccountOutlined | FiUserCheck | react-icons/fi |
| Report | FiAlertCircle | react-icons/fi |
| Assignment | FiBook | react-icons/fi |
| MoneyRounded | FiDollarSign | react-icons/fi |
| Visibility | FiEye | react-icons/fi |
| VisibilityOff | FiEyeOff | react-icons/fi |
| PersonRemove | FiUserMinus | react-icons/fi |
| Settings | FiSettings | react-icons/fi |
| Tune | FiSliders | react-icons/fi |

## Migration Patterns

### 1. Button Migration
```javascript
// Before (MUI)
import { Button } from '@mui/material';
<Button variant="contained" color="primary">Click</Button>

// After (shadcn/ui)
import { Button } from '@/components/ui/button';
<Button variant="default">Click</Button>
```

### 2. Input/TextField Migration
```javascript
// Before (MUI)
import { TextField } from '@mui/material';
<TextField label="Email" error={hasError} helperText="Error message" />

// After (shadcn/ui)
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
<div>
  <Label htmlFor="email">Email</Label>
  <Input id="email" className={hasError ? 'border-red-500' : ''} />
  {hasError && <p className="text-sm text-red-500">Error message</p>}
</div>
```

### 3. Snackbar → Toast Migration
```javascript
// Before (MUI)
import { Snackbar } from '@mui/material';
<Snackbar open={open} message="Success" />

// After (shadcn/ui)
import { useToast } from '@/hooks/use-toast';
const { toast } = useToast();
toast({ title: "Success", description: "Operation completed" });
```

### 4. Menu → DropdownMenu Migration
```javascript
// Before (MUI)
import { Menu, MenuItem } from '@mui/material';
<Menu anchorEl={anchorEl} open={open}>
  <MenuItem>Item 1</MenuItem>
</Menu>

// After (shadcn/ui)
import { DropdownMenu, DropdownMenuTrigger, DropdownMenuContent, DropdownMenuItem } from '@/components/ui/dropdown-menu';
<DropdownMenu>
  <DropdownMenuTrigger>Open</DropdownMenuTrigger>
  <DropdownMenuContent>
    <DropdownMenuItem>Item 1</DropdownMenuItem>
  </DropdownMenuContent>
</DropdownMenu>
```

### 5. Table Migration
```javascript
// Before (MUI)
import { Table, TableBody, TableCell, TableHead, TableRow } from '@mui/material';
<Table>
  <TableHead>
    <TableRow>
      <TableCell>Header</TableCell>
    </TableRow>
  </TableHead>
</Table>

// After (shadcn/ui)
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
<Table>
  <TableHeader>
    <TableRow>
      <TableHead>Header</TableHead>
    </TableRow>
  </TableHeader>
</Table>
```

### 6. Icon Migration
```javascript
// Before (MUI)
import HomeIcon from '@mui/icons-material/Home';
<HomeIcon color="primary" />

// After (React Icons)
import { FiHome } from 'react-icons/fi';
<FiHome className="text-purple-600" />
```

### 7. Styled Components → Tailwind
```javascript
// Before (styled-components)
import styled from 'styled-components';
const StyledButton = styled(Button)`
  background-color: #f00;
  &:hover { background-color: #eb7979; }
`;

// After (Tailwind)
import { Button } from '@/components/ui/button';
import { cn } from '@/lib/utils';
<Button className={cn('bg-red-600 hover:bg-red-500')}>Button</Button>
```

## Remaining Files to Migrate

### Admin Pages
- AdminDashboard.js
- AdminHomePage.js
- AdminProfile.js
- AdminRegisterPage.js
- App.js
- classRelated/*.js (multiple files)
- noticeRelated/*.js
- studentRelated/*.js
- subjectRelated/*.js
- teacherRelated/*.js
- feesPaymentRelated/*.js
- academicYear/*.js

### Student Pages
- All student-related pages

### Teacher Pages
- All teacher-related pages

### Exam Pages
- All exam-related pages

## Common Patterns for Remaining Files

1. **Replace MUI imports**
   - Remove all `@mui/material` and `@mui/icons-material` imports
   - Add shadcn/ui component imports from `@/components/ui/*`
   - Add react-icons imports from `react-icons/fi`

2. **Update component props**
   - `sx` prop → `className` with Tailwind classes
   - `variant` prop values may differ (check shadcn docs)
   - `color` prop → Tailwind color classes

3. **Convert inline styles**
   - `sx={{ mt: 2 }}` → `className="mt-2"`
   - `sx={{ display: 'flex' }}` → `className="flex"`
   - Complex styles → Tailwind utility classes

4. **Update event handlers**
   - Most event handlers remain the same
   - Check for MUI-specific event signatures

5. **Forms**
   - Replace `TextField` with `Input` + `Label`
   - Add error styling manually with conditional classes
   - Use `className` for validation states

## Testing Checklist

- [ ] All components render without errors
- [ ] Forms submit correctly
- [ ] Validation works properly
- [ ] Navigation functions as expected
- [ ] Responsive design maintained
- [ ] Dark mode works (if implemented)
- [ ] Icons display correctly
- [ ] Tables paginate properly
- [ ] Modals/Dialogs open and close
- [ ] Toasts/notifications appear

## Quick Reference Commands

```bash
# Install dependencies
npm install react-icons

# Add shadcn components as needed
npx shadcn@latest add [component-name]

# Remove MUI packages (already done)
npm uninstall @mui/material @mui/icons-material @mui/lab @emotion/react @emotion/styled

# Run dev server
npm run dev

# Build for production
npm run build
```

## Tips

1. **Use Find and Replace** for common patterns:
   - `@mui/material` → shadcn imports
   - `@mui/icons-material/` → react-icons imports
   
2. **Tailwind Classes** for common MUI sx patterns:
   - `sx={{ mt: 2 }}` → `className="mt-2"`
   - `sx={{ mb: 3 }}` → `className="mb-3"`
   - `sx={{ display: 'flex' }}` → `className="flex"`
   - `sx={{ justifyContent: 'space-between' }}` → `className="justify-between"`

3. **Color System**:
   - MUI primary → `purple-600` or `purple-700`
   - MUI error → `red-600` or `destructive` variant
   - MUI success → `green-600`
   - MUI warning → `yellow-600`

4. **Spacing**:
   - MUI uses 8px base unit
   - Tailwind uses 4px base unit (0.25rem)
   - MUI spacing(2) = 16px = Tailwind `4` (1rem)

## Support

For component-specific migration help, refer to:
- shadcn/ui docs: https://ui.shadcn.com
- React Icons: https://react-icons.github.io/react-icons
- Tailwind CSS: https://tailwindcss.com/docs
