# MUI to Shadcn Component Mapping Guide

## Component Mappings

### Basic Components

| MUI Component | Shadcn Component | Notes |
|--------------|------------------|-------|
| `<Button>` | `<Button>` | Direct replacement, adjust variant props |
| `<TextField>` | `<Input>` or `<Textarea>` | Use Input for single line, Textarea for multiline |
| `<Typography>` | Native HTML tags | Use `<h1>`, `<p>`, etc. with Tailwind classes |
| `<Box>` | `<div>` | Replace with div + Tailwind classes |
| `<Container>` | `<div>` | Use div with `container mx-auto` classes |
| `<Grid>` | `<div>` | Use `grid` or `flex` with Tailwind |
| `<Paper>` | `<Card>` | Use Card component |
| `<Stack>` | `<div>` | Use `flex flex-col` or `flex flex-row` |

### Form Components

| MUI Component | Shadcn Component | Notes |
|--------------|------------------|-------|
| `<TextField>` | `<Input>` + `<Label>` | Wrap Input with Label |
| `<Select>` | `<Select>` | Similar API |
| `<MenuItem>` | `<SelectItem>` | Used inside Select |
| `<Checkbox>` | `<Checkbox>` | Direct replacement |
| `<Radio>` | Need to install | Run: `npx shadcn-ui@latest add radio-group` |
| `<Switch>` | Need to install | Run: `npx shadcn-ui@latest add switch` |
| `<FormControl>` | `<div>` | Wrap with div |
| `<InputLabel>` | `<Label>` | Direct replacement |

### Data Display

| MUI Component | Shadcn Component | Notes |
|--------------|------------------|-------|
| `<Table>` | `<Table>` | Already installed |
| `<TableBody>` | `<TableBody>` | Direct replacement |
| `<TableCell>` | `<TableCell>` | Direct replacement |
| `<TableHead>` | `<TableHead>` | Direct replacement |
| `<TableRow>` | `<TableRow>` | Direct replacement |
| `<Chip>` | `<Badge>` | Already installed |
| `<Avatar>` | `<Avatar>` | Already installed |
| `<List>` | `<ul>` | Use HTML list with styling |
| `<ListItem>` | `<li>` | Use HTML list item |

### Feedback Components

| MUI Component | Shadcn Component | Notes |
|--------------|------------------|-------|
| `<Alert>` | `<Alert>` | Already installed |
| `<Dialog>` | `<Dialog>` | Already installed |
| `<Snackbar>` | `<Toast>` | Use toast system |
| `<CircularProgress>` | Need to install | Run: `npx shadcn-ui@latest add spinner` |
| `<LinearProgress>` | Need to install | Run: `npx shadcn-ui@latest add progress` |
| `<Backdrop>` | Custom | Create with Dialog or Sheet |

### Navigation

| MUI Component | Shadcn Component | Notes |
|--------------|------------------|-------|
| `<Drawer>` | `<Sheet>` | Already installed |
| `<Menu>` | `<DropdownMenu>` | Already installed |
| `<Tabs>` | `<Tabs>` | Already installed |
| `<AppBar>` | Custom | Create with flex layout |
| `<Toolbar>` | Custom | Use flex layout |

### Layout

| MUI Component | Shadcn Component | Notes |
|--------------|------------------|-------|
| `<Card>` | `<Card>` | Already installed |
| `<CardContent>` | `<CardContent>` | Already installed |
| `<CardHeader>` | `<CardHeader>` | Already installed |
| `<Divider>` | `<Separator>` | Already installed |
| `<Collapse>` | Need to install | Run: `npx shadcn-ui@latest add collapsible` |

## Icons Mapping

### MUI Icons to Lucide React

| MUI Icon | Lucide Icon | Import |
|----------|-------------|--------|
| `<KeyboardArrowDown>` | `<ChevronDown>` | `lucide-react` |
| `<KeyboardArrowUp>` | `<ChevronUp>` | `lucide-react` |
| `<Delete>` | `<Trash2>` | `lucide-react` |
| `<Edit>` | `<Pencil>` | `lucide-react` |
| `<Add>` | `<Plus>` | `lucide-react` |
| `<Close>` | `<X>` | `lucide-react` |
| `<Check>` | `<Check>` | `lucide-react` |
| `<Search>` | `<Search>` | `lucide-react` |
| `<AccountCircle>` | `<User>` | `lucide-react` |
| `<Settings>` | `<Settings>` | `lucide-react` |
| `<Home>` | `<Home>` | `lucide-react` |
| `<Menu>` | `<Menu>` | `lucide-react` |
| `<MoreVert>` | `<MoreVertical>` | `lucide-react` |

## Conversion Examples

### Button Conversion

**MUI:**
```jsx
<Button variant="contained" color="primary" onClick={handleClick}>
  Click Me
</Button>
```

**Shadcn:**
```jsx
<Button variant="default" onClick={handleClick}>
  Click Me
</Button>
```

### TextField Conversion

**MUI:**
```jsx
<TextField
  label="Name"
  value={name}
  onChange={handleChange}
  fullWidth
/>
```

**Shadcn:**
```jsx
<div className="w-full space-y-2">
  <Label htmlFor="name">Name</Label>
  <Input
    id="name"
    value={name}
    onChange={handleChange}
  />
</div>
```

### Select Conversion

**MUI:**
```jsx
<FormControl fullWidth>
  <InputLabel>Class</InputLabel>
  <Select value={selectedClass} onChange={handleChange}>
    <MenuItem value="1">Class 1</MenuItem>
    <MenuItem value="2">Class 2</MenuItem>
  </Select>
</FormControl>
```

**Shadcn:**
```jsx
<div className="w-full space-y-2">
  <Label>Class</Label>
  <Select value={selectedClass} onValueChange={handleChange}>
    <SelectTrigger>
      <SelectValue placeholder="Select class" />
    </SelectTrigger>
    <SelectContent>
      <SelectItem value="1">Class 1</SelectItem>
      <SelectItem value="2">Class 2</SelectItem>
    </SelectContent>
  </Select>
</div>
```

### Table Conversion

**MUI:**
```jsx
<Table>
  <TableHead>
    <TableRow>
      <TableCell>Name</TableCell>
      <TableCell>Age</TableCell>
    </TableRow>
  </TableHead>
  <TableBody>
    <TableRow>
      <TableCell>John</TableCell>
      <TableCell>25</TableCell>
    </TableRow>
  </TableBody>
</Table>
```

**Shadcn:**
```jsx
<Table>
  <TableHeader>
    <TableRow>
      <TableHead>Name</TableHead>
      <TableHead>Age</TableHead>
    </TableRow>
  </TableHeader>
  <TableBody>
    <TableRow>
      <TableCell>John</TableCell>
      <TableCell>25</TableCell>
    </TableRow>
  </TableBody>
</Table>
```

### Dialog Conversion

**MUI:**
```jsx
<Dialog open={open} onClose={handleClose}>
  <DialogTitle>Title</DialogTitle>
  <DialogContent>
    Content here
  </DialogContent>
  <DialogActions>
    <Button onClick={handleClose}>Cancel</Button>
    <Button onClick={handleSave}>Save</Button>
  </DialogActions>
</Dialog>
```

**Shadcn:**
```jsx
<Dialog open={open} onOpenChange={setOpen}>
  <DialogContent>
    <DialogHeader>
      <DialogTitle>Title</DialogTitle>
    </DialogHeader>
    <div>Content here</div>
    <DialogFooter>
      <Button variant="outline" onClick={handleClose}>Cancel</Button>
      <Button onClick={handleSave}>Save</Button>
    </DialogFooter>
  </DialogContent>
</Dialog>
```

### Typography Conversion

**MUI:**
```jsx
<Typography variant="h1">Heading</Typography>
<Typography variant="body1">Body text</Typography>
<Typography variant="caption">Small text</Typography>
```

**Shadcn (with Tailwind):**
```jsx
<h1 className="text-4xl font-bold">Heading</h1>
<p className="text-base">Body text</p>
<span className="text-sm text-muted-foreground">Small text</span>
```

## Common Patterns

### Grid Layout

**MUI:**
```jsx
<Grid container spacing={2}>
  <Grid item xs={12} md={6}>
    Content 1
  </Grid>
  <Grid item xs={12} md={6}>
    Content 2
  </Grid>
</Grid>
```

**Shadcn:**
```jsx
<div className="grid grid-cols-1 md:grid-cols-2 gap-4">
  <div>Content 1</div>
  <div>Content 2</div>
</div>
```

### Card with Actions

**MUI:**
```jsx
<Card>
  <CardContent>
    <Typography variant="h5">Title</Typography>
    <Typography>Description</Typography>
  </CardContent>
  <CardActions>
    <Button>Action</Button>
  </CardActions>
</Card>
```

**Shadcn:**
```jsx
<Card>
  <CardHeader>
    <CardTitle>Title</CardTitle>
    <CardDescription>Description</CardDescription>
  </CardHeader>
  <CardFooter>
    <Button>Action</Button>
  </CardFooter>
</Card>
```

## Installation Commands

Install missing Shadcn components:

```bash
# Navigate to frontend1
cd frontend1

# Install missing components
npx shadcn-ui@latest add radio-group
npx shadcn-ui@latest add switch
npx shadcn-ui@latest add progress
npx shadcn-ui@latest add collapsible
npx shadcn-ui@latest add popover
npx shadcn-ui@latest add tooltip
npx shadcn-ui@latest add accordion
```

## Dependencies to Install

```bash
npm install lucide-react
```

## Dependencies to Remove (After Conversion)

```bash
npm uninstall @mui/material @mui/icons-material @emotion/react @emotion/styled
```

## CSS/Styling Considerations

- Replace `sx` prop with `className` and Tailwind classes
- Use Tailwind utility classes for spacing, colors, etc.
- Use CSS variables for theming (already set up in Shadcn)

## Testing Checklist

After conversion, test:
- [ ] All buttons work correctly
- [ ] Forms submit properly
- [ ] Tables display data
- [ ] Modals/Dialogs open and close
- [ ] Navigation works
- [ ] Responsive layout on mobile
- [ ] Dark mode (if applicable)
- [ ] Icons display correctly
