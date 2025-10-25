# MUI to shadcn/ui Migration Helper Script
# This script helps automate common replacements for migrating from MUI to shadcn/ui

Write-Host "MUI to shadcn/ui Migration Helper" -ForegroundColor Cyan
Write-Host "=================================" -ForegroundColor Cyan
Write-Host ""

$srcPath = "C:\Users\SABITECK\OneDrive\Documenti\Projects\SABITECK\School Management System\frontend1\src"

# Icon mapping for replacements
$iconMappings = @{
    'import HomeIcon from "@mui/icons-material/Home";' = 'import { FiHome } from "react-icons/fi";'
    'import PersonOutlineIcon from "@mui/icons-material/PersonOutline";' = 'import { FiUsers } from "react-icons/fi";'
    'import ExitToAppIcon from "@mui/icons-material/ExitToApp";' = 'import { FiLogOut } from "react-icons/fi";'
    'import AccountCircleOutlinedIcon from "@mui/icons-material/AccountCircleOutlined";' = 'import { FiUser } from "react-icons/fi";'
    'import AnnouncementOutlinedIcon from "@mui/icons-material/AnnouncementOutlined";' = 'import { FiBell } from "react-icons/fi";'
    'import ClassOutlinedIcon from "@mui/icons-material/ClassOutlined";' = 'import { FiBook } from "react-icons/fi";'
    'import SupervisorAccountOutlinedIcon from "@mui/icons-material/SupervisorAccountOutlined";' = 'import { FiUserCheck } from "react-icons/fi";'
    'import ReportIcon from "@mui/icons-material/Report";' = 'import { FiAlertCircle } from "react-icons/fi";'
    'import AssignmentIcon from "@mui/icons-material/Assignment";' = 'import { FiBook } from "react-icons/fi";'
    'import { MoneyRounded } from "@mui/icons-material";' = 'import { FiDollarSign } from "react-icons/fi";'
    'import { Visibility, VisibilityOff } from "@mui/icons-material";' = 'import { FiEye, FiEyeOff } from "react-icons/fi";'
    'import PersonRemoveIcon from "@mui/icons-material/PersonRemove";' = 'import { FiUserMinus } from "react-icons/fi";'
    'import SettingsIcon from "@mui/icons-material/Settings";' = 'import { FiSettings } from "react-icons/fi";'
    'import DeleteIcon from "@mui/icons-material/Delete";' = 'import { FiTrash2 } from "react-icons/fi";'
    'import EditIcon from "@mui/icons-material/Edit";' = 'import { FiEdit } from "react-icons/fi";'
    'import AddIcon from "@mui/icons-material/Add";' = 'import { FiPlus } from "react-icons/fi";'
    'import CloseIcon from "@mui/icons-material/Close";' = 'import { FiX } from "react-icons/fi";'
    'import SearchIcon from "@mui/icons-material/Search";' = 'import { FiSearch } from "react-icons/fi";'
    'import MoreVertIcon from "@mui/icons-material/MoreVert";' = 'import { FiMoreVertical } from "react-icons/fi";'
    'import ChevronLeftIcon from "@mui/icons-material/ChevronLeft";' = 'import { FiChevronLeft } from "react-icons/fi";'
    'import ChevronRightIcon from "@mui/icons-material/ChevronRight";' = 'import { FiChevronRight } from "react-icons/fi";'
}

# Component usage replacements
$componentReplacements = @{
    '<HomeIcon' = '<FiHome'
    '<PersonOutlineIcon' = '<FiUsers'
    '<ExitToAppIcon' = '<FiLogOut'
    '<AccountCircleOutlinedIcon' = '<FiUser'
    '<AnnouncementOutlinedIcon' = '<FiBell'
    '<ClassOutlinedIcon' = '<FiBook'
    '<SupervisorAccountOutlinedIcon' = '<FiUserCheck'
    '<ReportIcon' = '<FiAlertCircle'
    '<AssignmentIcon' = '<FiBook'
    '<MoneyRounded' = '<FiDollarSign'
    '<Visibility' = '<FiEye'
    '<VisibilityOff' = '<FiEyeOff'
    '<PersonRemoveIcon' = '<FiUserMinus'
    '<SettingsIcon' = '<FiSettings'
    '<DeleteIcon' = '<FiTrash2'
    '<EditIcon' = '<FiEdit'
    '<AddIcon' = '<FiPlus'
    '<CloseIcon' = '<FiX'
    '<SearchIcon' = '<FiSearch'
    '<MoreVertIcon' = '<FiMoreVertical'
    '<ChevronLeftIcon' = '<FiChevronLeft'
    '<ChevronRightIcon' = '<FiChevronRight'
    'CircularProgress' = 'FiLoader'
}

# Function to create backup
function Create-Backup {
    param($filePath)
    $backupPath = $filePath + ".bak"
    if (-not (Test-Path $backupPath)) {
        Copy-Item $filePath $backupPath
        return $true
    }
    return $false
}

# Function to check if file has MUI imports
function Has-MUIImports {
    param($filePath)
    $content = Get-Content $filePath -Raw
    return $content -match '@mui/'
}

# Get all JS files with MUI imports
$muiFiles = Get-ChildItem -Path $srcPath -Recurse -Filter "*.js" | Where-Object { Has-MUIImports $_.FullName }

Write-Host "Found $($muiFiles.Count) files with MUI imports" -ForegroundColor Yellow
Write-Host ""

$choice = Read-Host "Do you want to see the list of files? (y/n)"
if ($choice -eq 'y') {
    $muiFiles | ForEach-Object { Write-Host $_.FullName -ForegroundColor Gray }
    Write-Host ""
}

Write-Host "Migration Options:" -ForegroundColor Cyan
Write-Host "1. Create backups of all files"
Write-Host "2. Show migration summary"
Write-Host "3. Exit"
Write-Host ""

$option = Read-Host "Select an option (1-3)"

switch ($option) {
    "1" {
        Write-Host "Creating backups..." -ForegroundColor Yellow
        $backupCount = 0
        $muiFiles | ForEach-Object {
            if (Create-Backup $_.FullName) {
                $backupCount++
            }
        }
        Write-Host "Created $backupCount backup files (.bak)" -ForegroundColor Green
        Write-Host "You can now manually edit files or use the migration guide." -ForegroundColor Cyan
    }
    "2" {
        Write-Host "`nMigration Summary:" -ForegroundColor Cyan
        Write-Host "==================" -ForegroundColor Cyan
        Write-Host "`nFiles by directory:" -ForegroundColor Yellow
        $muiFiles | Group-Object { Split-Path $_.DirectoryName -Leaf } | ForEach-Object {
            Write-Host "  $($_.Name): $($_.Count) files" -ForegroundColor Gray
        }
        Write-Host "`nRefer to MUI_TO_SHADCN_MIGRATION.md for detailed migration instructions." -ForegroundColor Cyan
    }
    "3" {
        Write-Host "Exiting..." -ForegroundColor Yellow
        exit
    }
}

Write-Host "`nNext Steps:" -ForegroundColor Cyan
Write-Host "1. Read MUI_TO_SHADCN_MIGRATION.md for detailed migration patterns" -ForegroundColor White
Write-Host "2. Update each file manually following the guide" -ForegroundColor White
Write-Host "3. Test the application after each major change" -ForegroundColor White
Write-Host "4. Run 'npm run dev' to check for errors" -ForegroundColor White
Write-Host ""
