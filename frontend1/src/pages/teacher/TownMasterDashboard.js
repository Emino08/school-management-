import { useMemo, useState } from "react";
import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card";
import { Shield, MapPin, PlusCircle, AlertCircle } from "lucide-react";
import { useSelector } from "react-redux";
import { Button } from "@/components/ui/button";
import StudentModal from "@/components/modals/StudentModal";

const TownMasterDashboard = () => {
  const { currentUser } = useSelector((state) => state.user);
  const [showStudentModal, setShowStudentModal] = useState(false);
  const isTownMaster =
    currentUser?.teacher?.is_town_master ||
    currentUser?.is_town_master ||
    currentUser?.roles?.includes?.("town_master");

  const preSelectedClass = useMemo(() => {
    const classes = currentUser?.teachClasses || currentUser?.teacher?.teachClasses || [];
    if (Array.isArray(classes) && classes.length > 0) {
      const first = classes[0];
      return first._id || first.id || "";
    }
    return "";
  }, [currentUser]);

  const name = currentUser?.name || currentUser?.teacher?.name || "Town Master";

  return (
    <div className="container mx-auto p-6 max-w-4xl space-y-6">
      <div className="flex items-center gap-3">
        <Shield className="w-8 h-8 text-indigo-600" />
        <div>
          <h1 className="text-3xl font-bold">Town Master</h1>
          <p className="text-gray-600">Special duties and reports for {name}</p>
        </div>
      </div>

      {!isTownMaster ? (
        <Card className="border-amber-200 bg-amber-50">
          <CardHeader>
            <CardTitle className="flex items-center gap-2 text-amber-800">
              <AlertCircle className="w-5 h-5" />
              Access Restricted
            </CardTitle>
          </CardHeader>
          <CardContent className="text-amber-800 space-y-2">
            <p>Your account is not marked as a Town Master. Please contact an administrator.</p>
          </CardContent>
        </Card>
      ) : (
        <Card>
          <CardHeader>
            <CardTitle className="flex items-center gap-2">
              <MapPin className="w-5 h-5 text-indigo-600" />
              Town / Block Management
            </CardTitle>
          </CardHeader>
          <CardContent className="space-y-4">
            <p className="text-sm text-gray-600">
              Add students to your assigned towns/blocks. Choose the class they belong to and their credentials.
            </p>
            <Button onClick={() => setShowStudentModal(true)} className="gap-2">
              <PlusCircle className="w-4 h-4" />
              Add Student to Town / Block
            </Button>
            <p className="text-xs text-gray-500">
              Use the same IDs and class assignments used by administrators. The backend permissions allow Town Masters to create students within their scope.
            </p>
          </CardContent>
        </Card>
      )}

      <StudentModal
        open={showStudentModal}
        onOpenChange={setShowStudentModal}
        preSelectedClass={preSelectedClass}
        onSuccess={() => setShowStudentModal(false)}
      />
    </div>
  );
};

export default TownMasterDashboard;
