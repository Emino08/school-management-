/**
 * MUI to Shadcn Conversion Script
 * This script helps automate the conversion of MUI components to Shadcn
 */

const fs = require('fs');
const path = require('path');

// Conversion mappings
const componentMappings = {
  // MUI imports to Shadcn imports
  imports: {
    'Button': '@/components/ui/button',
    'TextField': '@/components/ui/input',
    'Table': '@/components/ui/table',
    'TableBody': '@/components/ui/table',
    'TableCell': '@/components/ui/table',
    'TableHead': '@/components/ui/table',
    'TableRow': '@/components/ui/table',
    'Card': '@/components/ui/card',
    'CardContent': '@/components/ui/card',
    'CardActions': '@/components/ui/card',
    'Dialog': '@/components/ui/dialog',
    'DialogTitle': '@/components/ui/dialog',
    'DialogContent': '@/components/ui/dialog',
    'DialogActions': '@/components/ui/dialog',
    'Select': '@/components/ui/select',
    'MenuItem': '@/components/ui/select',
    'Checkbox': '@/components/ui/checkbox',
    'Alert': '@/components/ui/alert',
    'Avatar': '@/components/ui/avatar',
    'Badge': '@/components/ui/badge',
    'Separator': '@/components/ui/separator',
    'Tabs': '@/components/ui/tabs',
    'TabPanel': '@/components/ui/tabs',
    'Input': '@/components/ui/input',
    'Label': '@/components/ui/label',
    'Textarea': '@/components/ui/textarea',
  },

  // Icon mappings
  icons: {
    'KeyboardArrowDown': 'ChevronDown',
    'KeyboardArrowUp': 'ChevronUp',
    'Delete': 'Trash2',
    'Edit': 'Pencil',
    'Add': 'Plus',
    'Close': 'X',
    'Check': 'Check',
    'Search': 'Search',
    'AccountCircle': 'User',
    'Settings': 'Settings',
    'Home': 'Home',
    'Menu': 'Menu',
    'MoreVert': 'MoreVertical',
  },

  // Component replacements
  components: {
    'Box': 'div',
    'Container': 'div',
    'Stack': 'div',
    'Grid': 'div',
    'Paper': 'Card',
    'Typography': '', // Will be handled specially
  }
};

// Regex patterns for conversions
const patterns = {
  muiImport: /import\s+{([^}]+)}\s+from\s+["']@mui\/material["'];?/g,
  muiIconImport: /import\s+{([^}]+)}\s+from\s+["']@mui\/icons-material["'];?/g,
  sxProp: /sx={{([^}]+)}}/g,
  variantProp: /variant=["'](\w+)["']/g,
  colorProp: /color=["'](\w+)["']/g,
};

function convertFile(filePath) {
  console.log(`Converting: ${filePath}`);

  let content = fs.readFileSync(filePath, 'utf8');
  let hasChanges = false;

  // Step 1: Replace MUI imports with Shadcn imports
  content = content.replace(patterns.muiImport, (match, imports) => {
    hasChanges = true;
    const importList = imports.split(',').map(i => i.trim());
    const shadcnImports = {};

    importList.forEach(imp => {
      if (componentMappings.imports[imp]) {
        const source = componentMappings.imports[imp];
        if (!shadcnImports[source]) {
          shadcnImports[source] = [];
        }
        shadcnImports[source].push(imp);
      }
    });

    // Generate new import statements
    return Object.entries(shadcnImports)
      .map(([source, components]) => {
        // Handle special cases
        const componentList = components.map(c => {
          if (c === 'TableHead') return 'TableHead, TableHeader';
          if (c === 'TableCell') return 'TableCell';
          if (c === 'TableRow') return 'TableRow';
          if (c === 'TableBody') return 'TableBody';
          if (c === 'CardContent') return 'CardContent';
          if (c === 'CardActions') return 'CardFooter';
          if (c === 'DialogTitle') return 'DialogTitle, DialogHeader';
          if (c === 'DialogActions') return 'DialogFooter';
          if (c === 'MenuItem') return 'SelectItem';
          return c;
        }).join(', ');

        return `import { ${componentList} } from "${source}";`;
      })
      .join('\n');
  });

  // Step 2: Replace MUI icon imports with Lucide icons
  content = content.replace(patterns.muiIconImport, (match, imports) => {
    hasChanges = true;
    const importList = imports.split(',').map(i => i.trim());
    const lucideIcons = importList
      .map(icon => componentMappings.icons[icon] || icon)
      .join(', ');

    return `import { ${lucideIcons} } from "lucide-react";`;
  });

  // Step 3: Add necessary imports for className utility
  if (content.includes('className') && !content.includes('import { cn }')) {
    content = 'import { cn } from "@/lib/utils";\n' + content;
    hasChanges = true;
  }

  // Save if changes were made
  if (hasChanges) {
    // Create backup
    fs.writeFileSync(filePath + '.backup', fs.readFileSync(filePath));
    fs.writeFileSync(filePath, content);
    console.log(`✓ Converted: ${filePath}`);
    console.log(`  Backup created: ${filePath}.backup`);
  } else {
    console.log(`  No changes needed`);
  }

  return hasChanges;
}

// Get all files to convert
const filesToConvert = [
  // Add file paths here
];

// Main execution
if (require.main === module) {
  console.log('MUI to Shadcn Conversion Tool\n');
  console.log('This script will:');
  console.log('1. Replace MUI imports with Shadcn imports');
  console.log('2. Replace MUI icon imports with Lucide React icons');
  console.log('3. Create backups of all modified files');
  console.log('\nNote: Manual adjustments will still be needed for:');
  console.log('- Component props and API differences');
  console.log('- Layout components (Grid, Box, Stack)');
  console.log('- Typography variants');
  console.log('- sx prop conversions to className\n');

  let totalConverted = 0;

  // Example: Convert a single file
  // const testFile = './src/pages/teacher/TeacherViewStudent.js';
  // if (convertFile(testFile)) {
  //   totalConverted++;
  // }

  console.log(`\nTotal files converted: ${totalConverted}`);
  console.log('\nNext steps:');
  console.log('1. Review the converted files');
  console.log('2. Manually adjust component props');
  console.log('3. Convert layout components (Box -> div with className)');
  console.log('4. Convert Typography to HTML tags with Tailwind classes');
  console.log('5. Test each page thoroughly');
}

module.exports = { convertFile, componentMappings, patterns };
