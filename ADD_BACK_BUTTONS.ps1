# Script to add BackButton to multiple pages

$pages = @(
    @{
        Path = "admin\UserManagement.jsx"
        Import = "import BackButton from '@/components/BackButton';"
        Button = '<BackButton to="/Admin/dashboard" label="Back to Dashboard" />'
        InsertAfter = '<div className="p-6'
    },
    @{
        Path = "admin\NotificationsManagement.jsx"
        Import = "import BackButton from '@/components/BackButton';"
        Button = '<BackButton to="/Admin/dashboard" label="Back to Dashboard" />'
        InsertAfter = '<div className="p-6'
    },
    @{
        Path = "admin\TownMasterManagement.jsx"
        Import = "import BackButton from '@/components/BackButton';"
        Button = '<BackButton to="/Admin/dashboard" label="Back to Dashboard" />'
        InsertAfter = '<div className="p-6'
    },
    @{
        Path = "parent\ChildProfile.jsx"
        Import = "import BackButton from '@/components/BackButton';"
        Button = '<BackButton to="/parent/dashboard" label="Back to Dashboard" />'
        InsertAfter = '<div className="min-h-screen'
    },
    @{
        Path = "medical\CreateMedicalRecord.jsx"
        Import = "import BackButton from '@/components/BackButton';"
        Button = '<BackButton to="/medical/dashboard" label="Back to Dashboard" />'
        InsertAfter = '<div className="p-6'
    },
    @{
        Path = "examOfficer\VerificationDashboard.jsx"
        Import = "import BackButton from '@/components/BackButton';"
        Button = '<BackButton to="/ExamOfficer/dashboard" label="Back to Dashboard" />'
        InsertAfter = '<div className="min-h-screen'
    }
)

Write-Host "Pages to update: $($pages.Count)" -ForegroundColor Cyan
foreach ($page in $pages) {
    Write-Host "  - $($page.Path)" -ForegroundColor White
}
