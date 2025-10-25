import React, { useState, useEffect } from "react";
import { useSelector } from "react-redux";
import axios from "axios";
import { toast } from "sonner";
import { Card, CardHeader, CardTitle, CardDescription, CardContent } from "../../../components/ui/card";
import { Label } from "../../../components/ui/label";
import { Input } from "../../../components/ui/input";
import { Button } from "../../../components/ui/button";
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from "../../../components/ui/select";
import {
  Table,
  TableBody,
  TableCell,
  TableHead,
  TableHeader,
  TableRow,
} from "../../../components/ui/table";
import { AlertDialog, AlertDialogAction, AlertDialogCancel, AlertDialogContent, AlertDialogDescription, AlertDialogFooter, AlertDialogHeader, AlertDialogTitle } from "../../../components/ui/alert-dialog";
import { Badge } from "../../../components/ui/badge";
import { Plus, Trash2, Edit, Award } from "lucide-react";

const GradingSystemConfig = () => {
  const { currentUser } = useSelector((state) => state.user);
  const [gradingScheme, setGradingScheme] = useState([]);
  const [academicYears, setAcademicYears] = useState([]);
  const [selectedYear, setSelectedYear] = useState("default");
  const [loading, setLoading] = useState(false);
  const [showAddDialog, setShowAddDialog] = useState(false);
  const [showDeleteDialog, setShowDeleteDialog] = useState(false);
  const [gradeToDelete, setGradeToDelete] = useState(null);
  const [editingGrade, setEditingGrade] = useState(null);

  const [formData, setFormData] = useState({
    grade_label: "",
    min_score: "",
    max_score: "",
    grade_point: "",
    description: "",
    is_passing: true
  });

  const API_URL = import.meta.env.VITE_API_BASE_URL || "http://localhost:8080/api";

  useEffect(() => {
    fetchAcademicYears();
    fetchGradingScheme();
  }, [selectedYear]);

  const fetchAcademicYears = async () => {
    try {
      const response = await axios.get(`${API_URL}/academic-years`, {
        headers: { Authorization: `Bearer ${currentUser?.token}` }
      });
      if (response.data.success) {
        setAcademicYears(response.data.academic_years || []);
      }
    } catch (error) {
      console.error("Error fetching academic years:", error);
    }
  };

  const fetchGradingScheme = async () => {
    setLoading(true);
    try {
      const params = (selectedYear && selectedYear !== "default") ? `?academic_year_id=${selectedYear}` : "";
      const response = await axios.get(`${API_URL}/grading-system${params}`, {
        headers: { Authorization: `Bearer ${currentUser?.token}` }
      });
      if (response.data.success) {
        setGradingScheme(response.data.grading_scheme || []);
      }
    } catch (error) {
      toast.error("Failed to fetch grading scheme");
    } finally {
      setLoading(false);
    }
  };

  const createPreset = async (presetType) => {
    setLoading(true);
    try {
      const response = await axios.post(
        `${API_URL}/grading-system/preset`,
        {
          preset_type: presetType,
          academic_year_id: (selectedYear && selectedYear !== "default") ? selectedYear : null
        },
        {
          headers: { Authorization: `Bearer ${currentUser?.token}` }
        }
      );

      if (response.data.success) {
        toast.success(`Created ${response.data.created_count} grade ranges`);
        fetchGradingScheme();
      }
    } catch (error) {
      toast.error(error.response?.data?.message || "Failed to create preset");
    } finally {
      setLoading(false);
    }
  };

  const handleInputChange = (e) => {
    const { name, value, type, checked } = e.target;
    setFormData({
      ...formData,
      [name]: type === "checkbox" ? checked : value
    });
  };

  const validateGradeRange = () => {
    const minScore = parseFloat(formData.min_score);
    const maxScore = parseFloat(formData.max_score);

    if (minScore >= maxScore) {
      toast.error("Minimum score must be less than maximum score");
      return false;
    }

    if (minScore < 0 || maxScore > 100) {
      toast.error("Scores must be between 0 and 100");
      return false;
    }

    return true;
  };

  const handleSubmit = async (e) => {
    e.preventDefault();

    if (!validateGradeRange()) return;

    setLoading(true);
    try {
      const gradeData = {
        academic_year_id: (selectedYear && selectedYear !== "default") ? selectedYear : null,
        grade_label: formData.grade_label.toUpperCase(),
        min_score: parseFloat(formData.min_score),
        max_score: parseFloat(formData.max_score),
        grade_point: formData.grade_point ? parseFloat(formData.grade_point) : null,
        description: formData.description,
        is_passing: formData.is_passing
      };

      if (editingGrade) {
        // Update existing grade
        await axios.put(
          `${API_URL}/grading-system/${editingGrade.id}`,
          gradeData,
          {
            headers: { Authorization: `Bearer ${currentUser?.token}` }
          }
        );
        toast.success("Grade range updated successfully");
      } else {
        // Create new grade
        await axios.post(
          `${API_URL}/grading-system`,
          gradeData,
          {
            headers: { Authorization: `Bearer ${currentUser?.token}` }
          }
        );
        toast.success("Grade range created successfully");
      }

      // Reset form
      setFormData({
        grade_label: "",
        min_score: "",
        max_score: "",
        grade_point: "",
        description: "",
        is_passing: true
      });
      setEditingGrade(null);
      setShowAddDialog(false);
      fetchGradingScheme();
    } catch (error) {
      toast.error(error.response?.data?.message || "Failed to save grade range");
    } finally {
      setLoading(false);
    }
  };

  const handleEdit = (grade) => {
    setEditingGrade(grade);
    setFormData({
      grade_label: grade.grade_label,
      min_score: grade.min_score,
      max_score: grade.max_score,
      grade_point: grade.grade_point || "",
      description: grade.description || "",
      is_passing: grade.is_passing === 1 || grade.is_passing === true
    });
    setShowAddDialog(true);
  };

  const handleDelete = async () => {
    if (!gradeToDelete) return;

    setLoading(true);
    try {
      await axios.delete(`${API_URL}/grading-system/${gradeToDelete.id}`, {
        headers: { Authorization: `Bearer ${currentUser?.token}` }
      });
      toast.success("Grade range deleted successfully");
      fetchGradingScheme();
      setShowDeleteDialog(false);
      setGradeToDelete(null);
    } catch (error) {
      toast.error("Failed to delete grade range");
    } finally {
      setLoading(false);
    }
  };

  return (
    <div className="p-6 max-w-6xl mx-auto space-y-6">
      {/* Header Card */}
      <Card>
        <CardHeader>
          <CardTitle className="text-2xl flex items-center gap-2">
            <Award className="w-6 h-6" />
            Grading System Configuration
          </CardTitle>
          <CardDescription>
            Configure grade ranges and scoring for academic years
          </CardDescription>
        </CardHeader>
        <CardContent className="space-y-4">
          {/* Academic Year Selector */}
          <div className="flex gap-4 items-end">
            <div className="flex-1 space-y-2">
              <Label>Academic Year (Optional)</Label>
              <Select value={selectedYear} onValueChange={setSelectedYear}>
                <SelectTrigger>
                  <SelectValue placeholder="All Years (Default)" />
                </SelectTrigger>
                <SelectContent>
                  <SelectItem value="default">All Years (Default)</SelectItem>
                  {academicYears.map((year) => (
                    <SelectItem key={year.id} value={year.id.toString()}>
                      {year.year_name}
                    </SelectItem>
                  ))}
                </SelectContent>
              </Select>
            </div>

            <Button onClick={() => setShowAddDialog(true)} className="gap-2">
              <Plus className="w-4 h-4" />
              Add Grade Range
            </Button>
          </div>

          {/* Quick Preset Buttons */}
          <div className="flex gap-2 flex-wrap">
            <div className="text-sm font-semibold mr-2 self-center">Quick Setup:</div>
            <Button
              variant="outline"
              size="sm"
              onClick={() => createPreset("average")}
              disabled={loading}
            >
              Average-Based (A-F)
            </Button>
            <Button
              variant="outline"
              size="sm"
              onClick={() => createPreset("gpa_5")}
              disabled={loading}
            >
              GPA 5.0 System
            </Button>
            <Button
              variant="outline"
              size="sm"
              onClick={() => createPreset("gpa_4")}
              disabled={loading}
            >
              GPA 4.0 System
            </Button>
          </div>
        </CardContent>
      </Card>

      {/* Grading Scheme Table */}
      <Card>
        <CardHeader>
          <CardTitle>Current Grading Scheme</CardTitle>
          <CardDescription>
            {selectedYear && selectedYear !== "default"
              ? `Grading scheme for selected academic year`
              : `Default grading scheme (applies to all years without specific configuration)`}
          </CardDescription>
        </CardHeader>
        <CardContent>
          {loading ? (
            <div className="text-center py-8">Loading...</div>
          ) : gradingScheme.length === 0 ? (
            <div className="text-center py-8 text-gray-500">
              No grading scheme configured. Use quick setup or add custom grade ranges.
            </div>
          ) : (
            <Table>
              <TableHeader>
                <TableRow>
                  <TableHead>Grade</TableHead>
                  <TableHead>Score Range</TableHead>
                  <TableHead>Grade Point</TableHead>
                  <TableHead>Description</TableHead>
                  <TableHead>Status</TableHead>
                  <TableHead className="text-right">Actions</TableHead>
                </TableRow>
              </TableHeader>
              <TableBody>
                {gradingScheme.map((grade) => (
                  <TableRow key={grade.id}>
                    <TableCell className="font-bold text-lg">{grade.grade_label}</TableCell>
                    <TableCell>
                      {grade.min_score} - {grade.max_score}
                    </TableCell>
                    <TableCell>
                      {grade.grade_point ? `${grade.grade_point} GPA` : "N/A"}
                    </TableCell>
                    <TableCell>{grade.description || "—"}</TableCell>
                    <TableCell>
                      {grade.is_passing ? (
                        <Badge variant="success">Passing</Badge>
                      ) : (
                        <Badge variant="destructive">Fail</Badge>
                      )}
                    </TableCell>
                    <TableCell className="text-right">
                      <div className="flex justify-end gap-2">
                        <Button
                          variant="ghost"
                          size="sm"
                          onClick={() => handleEdit(grade)}
                        >
                          <Edit className="w-4 h-4" />
                        </Button>
                        <Button
                          variant="ghost"
                          size="sm"
                          onClick={() => {
                            setGradeToDelete(grade);
                            setShowDeleteDialog(true);
                          }}
                        >
                          <Trash2 className="w-4 h-4 text-red-500" />
                        </Button>
                      </div>
                    </TableCell>
                  </TableRow>
                ))}
              </TableBody>
            </Table>
          )}
        </CardContent>
      </Card>

      {/* Add/Edit Grade Dialog */}
      {showAddDialog && (
        <div className="fixed inset-0 bg-black/50 flex items-center justify-center z-50 p-4">
          <Card className="max-w-md w-full">
            <CardHeader>
              <CardTitle>{editingGrade ? "Edit" : "Add"} Grade Range</CardTitle>
            </CardHeader>
            <CardContent>
              <form onSubmit={handleSubmit} className="space-y-4">
                <div className="space-y-2">
                  <Label htmlFor="grade_label">Grade Label *</Label>
                  <Input
                    id="grade_label"
                    name="grade_label"
                    value={formData.grade_label}
                    onChange={handleInputChange}
                    placeholder="e.g., A, B, C"
                    maxLength={5}
                    required
                  />
                </div>

                <div className="grid grid-cols-2 gap-4">
                  <div className="space-y-2">
                    <Label htmlFor="min_score">Min Score *</Label>
                    <Input
                      id="min_score"
                      name="min_score"
                      type="number"
                      step="0.01"
                      value={formData.min_score}
                      onChange={handleInputChange}
                      placeholder="0"
                      required
                    />
                  </div>

                  <div className="space-y-2">
                    <Label htmlFor="max_score">Max Score *</Label>
                    <Input
                      id="max_score"
                      name="max_score"
                      type="number"
                      step="0.01"
                      value={formData.max_score}
                      onChange={handleInputChange}
                      placeholder="100"
                      required
                    />
                  </div>
                </div>

                <div className="space-y-2">
                  <Label htmlFor="grade_point">Grade Point (Optional)</Label>
                  <Input
                    id="grade_point"
                    name="grade_point"
                    type="number"
                    step="0.01"
                    value={formData.grade_point}
                    onChange={handleInputChange}
                    placeholder="e.g., 4.0"
                  />
                </div>

                <div className="space-y-2">
                  <Label htmlFor="description">Description</Label>
                  <Input
                    id="description"
                    name="description"
                    value={formData.description}
                    onChange={handleInputChange}
                    placeholder="e.g., Excellent, Good"
                  />
                </div>

                <div className="flex items-center space-x-2">
                  <input
                    type="checkbox"
                    id="is_passing"
                    name="is_passing"
                    checked={formData.is_passing}
                    onChange={handleInputChange}
                    className="h-4 w-4"
                  />
                  <Label htmlFor="is_passing">This is a passing grade</Label>
                </div>

                <div className="flex gap-2">
                  <Button type="submit" disabled={loading} className="flex-1">
                    {loading ? "Saving..." : editingGrade ? "Update" : "Create"}
                  </Button>
                  <Button
                    type="button"
                    variant="outline"
                    onClick={() => {
                      setShowAddDialog(false);
                      setEditingGrade(null);
                      setFormData({
                        grade_label: "",
                        min_score: "",
                        max_score: "",
                        grade_point: "",
                        description: "",
                        is_passing: true
                      });
                    }}
                  >
                    Cancel
                  </Button>
                </div>
              </form>
            </CardContent>
          </Card>
        </div>
      )}

      {/* Delete Confirmation Dialog */}
      <AlertDialog open={showDeleteDialog} onOpenChange={setShowDeleteDialog}>
        <AlertDialogContent>
          <AlertDialogHeader>
            <AlertDialogTitle>Delete Grade Range</AlertDialogTitle>
            <AlertDialogDescription>
              Are you sure you want to delete grade {gradeToDelete?.grade_label}? This action cannot be undone.
            </AlertDialogDescription>
          </AlertDialogHeader>
          <AlertDialogFooter>
            <AlertDialogCancel onClick={() => setGradeToDelete(null)}>
              Cancel
            </AlertDialogCancel>
            <AlertDialogAction onClick={handleDelete} className="bg-red-600">
              Delete
            </AlertDialogAction>
          </AlertDialogFooter>
        </AlertDialogContent>
      </AlertDialog>
    </div>
  );
};

export default GradingSystemConfig;
