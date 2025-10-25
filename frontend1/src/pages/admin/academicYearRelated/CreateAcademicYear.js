import React, { useState, useEffect } from "react";
import { useDispatch, useSelector } from "react-redux";
import { useNavigate } from "react-router-dom";
import { createAcademicYear, getAllAcademicYears } from "../../../redux/academicYearRelated/academicYearHandle";
import { toast } from "sonner";
import { Card, CardHeader, CardTitle, CardDescription, CardContent } from "../../../components/ui/card";
import { Label } from "../../../components/ui/label";
import { Input } from "../../../components/ui/input";
import { Button } from "../../../components/ui/button";
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from "../../../components/ui/select";
import { Badge } from "../../../components/ui/badge";
import { Tabs, TabsContent, TabsList, TabsTrigger } from "../../../components/ui/tabs";
import { Calendar, Plus, List, Award, DollarSign } from "lucide-react";
import axios from "axios";

const CreateAcademicYear = () => {
  const dispatch = useDispatch();
  const navigate = useNavigate();
  const { currentUser } = useSelector((state) => state.user);
  const { academicYearData } = useSelector((state) => state.academicYear);

  const [formData, setFormData] = useState({
    year_name: "",
    start_date: "",
    end_date: "",
    number_of_terms: "3",
    exams_per_term: "1",
    grading_type: "average",
    term_1_fee: "",
    term_1_min_payment: "",
    term_2_fee: "",
    term_2_min_payment: "",
    term_3_fee: "",
    term_3_min_payment: "",
    result_publication_date: "",
    auto_calculate_position: true,
    is_current: false
  });

  const [loading, setLoading] = useState(false);
  const [activeTab, setActiveTab] = useState("create");
  const [gradeRanges, setGradeRanges] = useState([]);
  const [showGradeForm, setShowGradeForm] = useState(false);
  const [gradeFormData, setGradeFormData] = useState({
    grade_label: "",
    min_score: "",
    max_score: "",
    grade_point: "",
    description: ""
  });

  const API_URL = import.meta.env.VITE_API_BASE_URL || "http://localhost:8080/api";

  useEffect(() => {
    dispatch(getAllAcademicYears());
  }, [dispatch]);

  const handleInputChange = (e) => {
    const { name, value, type, checked } = e.target;
    setFormData({
      ...formData,
      [name]: type === "checkbox" ? checked : value
    });
  };

  const handleSelectChange = (name, value) => {
    setFormData({
      ...formData,
      [name]: value
    });
  };

  const generateYearName = () => {
    const currentYear = new Date().getFullYear();
    const nextYear = currentYear + 1;
    setFormData({
      ...formData,
      year_name: `${currentYear}-${nextYear}`
    });
  };

  const handleSubmit = async (e) => {
    e.preventDefault();
    setLoading(true);

    try {
      const academicYearData = {
        year_name: formData.year_name,
        start_date: formData.start_date,
        end_date: formData.end_date,
        number_of_terms: parseInt(formData.number_of_terms),
        exams_per_term: parseInt(formData.exams_per_term),
        grading_type: formData.grading_type,
        term_1_fee: parseFloat(formData.term_1_fee) || 0,
        term_1_min_payment: parseFloat(formData.term_1_min_payment) || (parseFloat(formData.term_1_fee) * 0.5) || 0,
        term_2_fee: parseFloat(formData.term_2_fee) || 0,
        term_2_min_payment: parseFloat(formData.term_2_min_payment) || (parseFloat(formData.term_2_fee) * 0.5) || 0,
        term_3_fee: formData.number_of_terms === "3" ? (parseFloat(formData.term_3_fee) || 0) : null,
        term_3_min_payment: formData.number_of_terms === "3" ? (parseFloat(formData.term_3_min_payment) || (parseFloat(formData.term_3_fee) * 0.5) || 0) : null,
        result_publication_date: formData.result_publication_date || null,
        auto_calculate_position: formData.auto_calculate_position,
        is_current: formData.is_current
      };

      const result = await dispatch(createAcademicYear(academicYearData));
      
      if (result && result.success) {
        toast.success("Academic year created successfully!");
        
        // Auto-create grading system if not custom
        if (formData.grading_type !== "custom" && gradeRanges.length === 0) {
          await createGradingPreset(result.academic_year_id, formData.grading_type);
        } else if (gradeRanges.length > 0) {
          await createCustomGrades(result.academic_year_id);
        }
        
        // Refresh the list
        dispatch(getAllAcademicYears());
        
        // Switch to list view
        setActiveTab("list");
        
        // Reset form
        setFormData({
          year_name: "",
          start_date: "",
          end_date: "",
          number_of_terms: "3",
          exams_per_term: "1",
          grading_type: "average",
          term_1_fee: "",
          term_1_min_payment: "",
          term_2_fee: "",
          term_2_min_payment: "",
          term_3_fee: "",
          term_3_min_payment: "",
          result_publication_date: "",
          auto_calculate_position: true,
          is_current: false
        });
        setGradeRanges([]);
      }
    } catch (error) {
      toast.error(error?.response?.data?.message || error.message || "Failed to create academic year");
    } finally {
      setLoading(false);
    }
  };

  const createGradingPreset = async (academicYearId, presetType) => {
    try {
      await axios.post(
        `${API_URL}/grading-system/preset`,
        {
          preset_type: presetType,
          academic_year_id: academicYearId
        },
        {
          headers: { Authorization: `Bearer ${currentUser?.token}` }
        }
      );
      toast.success("Grading system configured successfully!");
    } catch (error) {
      toast.warning("Academic year created but failed to setup grading system. You can set it up manually.");
    }
  };

  const createCustomGrades = async (academicYearId) => {
    try {
      for (const grade of gradeRanges) {
        await axios.post(
          `${API_URL}/grading-system`,
          {
            academic_year_id: academicYearId,
            grade_label: grade.grade_label,
            min_score: parseFloat(grade.min_score),
            max_score: parseFloat(grade.max_score),
            grade_point: grade.grade_point ? parseFloat(grade.grade_point) : null,
            description: grade.description,
            is_passing: parseFloat(grade.min_score) >= 50
          },
          {
            headers: { Authorization: `Bearer ${currentUser?.token}` }
          }
        );
      }
      toast.success("Custom grading system configured successfully!");
    } catch (error) {
      toast.warning("Academic year created but failed to setup custom grades. You can set them up manually.");
    }
  };

  const addGradeRange = () => {
    if (!gradeFormData.grade_label || !gradeFormData.min_score || !gradeFormData.max_score) {
      toast.error("Please fill all required fields");
      return;
    }

    if (parseFloat(gradeFormData.min_score) >= parseFloat(gradeFormData.max_score)) {
      toast.error("Min score must be less than max score");
      return;
    }

    setGradeRanges([...gradeRanges, { ...gradeFormData }]);
    setGradeFormData({
      grade_label: "",
      min_score: "",
      max_score: "",
      grade_point: "",
      description: ""
    });
    setShowGradeForm(false);
    toast.success("Grade range added");
  };

  const removeGradeRange = (index) => {
    setGradeRanges(gradeRanges.filter((_, i) => i !== index));
    toast.success("Grade range removed");
  };

  const totalFees = () => {
    const term1 = parseFloat(formData.term_1_fee) || 0;
    const term2 = parseFloat(formData.term_2_fee) || 0;
    const term3 = formData.number_of_terms === "3" ? (parseFloat(formData.term_3_fee) || 0) : 0;
    return term1 + term2 + term3;
  };

  const academicYears = Array.isArray(academicYearData) ? academicYearData : [];

  return (
    <div className="p-6 max-w-6xl mx-auto">
      <Card>
        <CardHeader>
          <CardTitle className="text-2xl flex items-center gap-2">
            <Calendar className="w-6 h-6" />
            Academic Year Management
          </CardTitle>
          <CardDescription>
            Create and manage academic years with terms, exams, and grading systems
          </CardDescription>
        </CardHeader>
        <CardContent>
          <Tabs value={activeTab} onValueChange={setActiveTab}>
            <TabsList className="grid w-full grid-cols-2">
              <TabsTrigger value="create" className="gap-2">
                <Plus className="w-4 h-4" />
                Create New
              </TabsTrigger>
              <TabsTrigger value="list" className="gap-2">
                <List className="w-4 h-4" />
                View All ({academicYears.length})
              </TabsTrigger>
            </TabsList>

            <TabsContent value="create" className="mt-6">
              <form onSubmit={handleSubmit} className="space-y-6">
            {/* Basic Information */}
            <div className="space-y-4">
              <h3 className="text-lg font-semibold border-b pb-2">Basic Information</h3>

              <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div className="space-y-2">
                  <Label htmlFor="year_name">Academic Year Name *</Label>
                  <div className="flex gap-2">
                    <Input
                      id="year_name"
                      name="year_name"
                      value={formData.year_name}
                      onChange={handleInputChange}
                      placeholder="e.g., 2024-2025"
                      required
                      className="flex-1"
                    />
                    <Button type="button" onClick={generateYearName} variant="outline">
                      Auto
                    </Button>
                  </div>
                </div>

                <div className="space-y-2">
                  <Label>
                    <input
                      type="checkbox"
                      name="is_current"
                      checked={formData.is_current}
                      onChange={handleInputChange}
                      className="mr-2"
                    />
                    Set as current academic year
                  </Label>
                </div>
              </div>

              <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div className="space-y-2">
                  <Label htmlFor="start_date">Start Date *</Label>
                  <Input
                    id="start_date"
                    name="start_date"
                    type="date"
                    value={formData.start_date}
                    onChange={handleInputChange}
                    required
                  />
                </div>

                <div className="space-y-2">
                  <Label htmlFor="end_date">End Date *</Label>
                  <Input
                    id="end_date"
                    name="end_date"
                    type="date"
                    value={formData.end_date}
                    onChange={handleInputChange}
                    required
                  />
                </div>
              </div>
            </div>

            {/* Term Configuration */}
            <div className="space-y-4">
              <h3 className="text-lg font-semibold border-b pb-2">Term Configuration</h3>

              <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div className="space-y-2">
                  <Label htmlFor="number_of_terms">Number of Terms *</Label>
                  <Select
                    value={formData.number_of_terms}
                    onValueChange={(value) => handleSelectChange("number_of_terms", value)}
                  >
                    <SelectTrigger>
                      <SelectValue />
                    </SelectTrigger>
                    <SelectContent>
                      <SelectItem value="2">2 Terms (Semester System)</SelectItem>
                      <SelectItem value="3">3 Terms (Trimester System)</SelectItem>
                    </SelectContent>
                  </Select>
                </div>

                <div className="space-y-2">
                  <Label htmlFor="exams_per_term">Exams Per Term *</Label>
                  <Select
                    value={formData.exams_per_term}
                    onValueChange={(value) => handleSelectChange("exams_per_term", value)}
                  >
                    <SelectTrigger>
                      <SelectValue />
                    </SelectTrigger>
                    <SelectContent>
                      <SelectItem value="1">1 Exam (Final Only)</SelectItem>
                      <SelectItem value="2">2 Exams (Midterm + Final)</SelectItem>
                    </SelectContent>
                  </Select>
                </div>
              </div>

              <div className="bg-blue-50 p-3 rounded-md text-sm">
                <strong>Total Exams:</strong> {parseInt(formData.number_of_terms) * parseInt(formData.exams_per_term)} exams per year
              </div>
            </div>

            {/* Grading System */}
            <div className="space-y-4">
              <h3 className="text-lg font-semibold border-b pb-2">Grading System</h3>

              <div className="space-y-2">
                <Label htmlFor="grading_type">Grading Type *</Label>
                <Select
                  value={formData.grading_type}
                  onValueChange={(value) => handleSelectChange("grading_type", value)}
                >
                  <SelectTrigger>
                    <SelectValue />
                  </SelectTrigger>
                  <SelectContent>
                    <SelectItem value="average">Average-Based</SelectItem>
                    <SelectItem value="custom">Custom Grade Ranges</SelectItem>
                  </SelectContent>
                </Select>
                <p className="text-xs text-gray-500">
                  {formData.grading_type === "average" && "Numerical average-based grading (mean of all scores)"}
                  {formData.grading_type === "custom" && "Define your own grade ranges below"}
                </p>
              </div>

              <div className="space-y-2">
                <Label>
                  <input
                    type="checkbox"
                    name="auto_calculate_position"
                    checked={formData.auto_calculate_position}
                    onChange={handleInputChange}
                    className="mr-2"
                  />
                  Auto-calculate student positions/rankings
                </Label>
              </div>

              <div className="space-y-2">
                <Label htmlFor="result_publication_date">Default Result Publication Date</Label>
                <Input
                  id="result_publication_date"
                  name="result_publication_date"
                  type="date"
                  value={formData.result_publication_date}
                  onChange={handleInputChange}
                />
                <p className="text-xs text-gray-500">
                  Default date when results will be visible to students
                </p>
              </div>
            </div>

            {/* Fees Configuration */}
            <div className="space-y-4">
              <h3 className="text-lg font-semibold border-b pb-2 flex items-center gap-2">
                <DollarSign className="w-5 h-5" />
                Fees Configuration
              </h3>

              <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                {/* Term 1 Fees */}
                <div className="space-y-3 p-4 border rounded-lg">
                  <h4 className="font-semibold text-sm">Term 1</h4>
                  <div className="space-y-2">
                    <Label htmlFor="term_1_fee">Total Fee *</Label>
                    <Input
                      id="term_1_fee"
                      name="term_1_fee"
                      type="number"
                      step="0.01"
                      value={formData.term_1_fee}
                      onChange={handleInputChange}
                      placeholder="0.00"
                      required
                    />
                  </div>
                  <div className="space-y-2">
                    <Label htmlFor="term_1_min_payment">Minimum Payment *</Label>
                    <Input
                      id="term_1_min_payment"
                      name="term_1_min_payment"
                      type="number"
                      step="0.01"
                      value={formData.term_1_min_payment}
                      onChange={handleInputChange}
                      placeholder={formData.term_1_fee ? (parseFloat(formData.term_1_fee) * 0.5).toFixed(2) : "0.00"}
                    />
                    <p className="text-xs text-gray-500">Minimum to be considered fully paid</p>
                  </div>
                </div>

                {/* Term 2 Fees */}
                <div className="space-y-3 p-4 border rounded-lg">
                  <h4 className="font-semibold text-sm">Term 2</h4>
                  <div className="space-y-2">
                    <Label htmlFor="term_2_fee">Total Fee *</Label>
                    <Input
                      id="term_2_fee"
                      name="term_2_fee"
                      type="number"
                      step="0.01"
                      value={formData.term_2_fee}
                      onChange={handleInputChange}
                      placeholder="0.00"
                      required
                    />
                  </div>
                  <div className="space-y-2">
                    <Label htmlFor="term_2_min_payment">Minimum Payment *</Label>
                    <Input
                      id="term_2_min_payment"
                      name="term_2_min_payment"
                      type="number"
                      step="0.01"
                      value={formData.term_2_min_payment}
                      onChange={handleInputChange}
                      placeholder={formData.term_2_fee ? (parseFloat(formData.term_2_fee) * 0.5).toFixed(2) : "0.00"}
                    />
                    <p className="text-xs text-gray-500">Minimum to be considered fully paid</p>
                  </div>
                </div>

                {/* Term 3 Fees */}
                {formData.number_of_terms === "3" && (
                  <div className="space-y-3 p-4 border rounded-lg">
                    <h4 className="font-semibold text-sm">Term 3</h4>
                    <div className="space-y-2">
                      <Label htmlFor="term_3_fee">Total Fee *</Label>
                      <Input
                        id="term_3_fee"
                        name="term_3_fee"
                        type="number"
                        step="0.01"
                        value={formData.term_3_fee}
                        onChange={handleInputChange}
                        placeholder="0.00"
                        required
                      />
                    </div>
                    <div className="space-y-2">
                      <Label htmlFor="term_3_min_payment">Minimum Payment *</Label>
                      <Input
                        id="term_3_min_payment"
                        name="term_3_min_payment"
                        type="number"
                        step="0.01"
                        value={formData.term_3_min_payment}
                        onChange={handleInputChange}
                        placeholder={formData.term_3_fee ? (parseFloat(formData.term_3_fee) * 0.5).toFixed(2) : "0.00"}
                      />
                      <p className="text-xs text-gray-500">Minimum to be considered fully paid</p>
                    </div>
                  </div>
                )}
              </div>

              <div className="bg-green-50 p-4 rounded-md border border-green-200">
                <div className="flex justify-between items-center">
                  <span className="font-semibold text-green-900">Total Annual Fee:</span>
                  <span className="text-2xl font-bold text-green-700">
                    ${totalFees().toFixed(2)}
                  </span>
                </div>
              </div>
            </div>

            {/* Grading System Configuration */}
            {formData.grading_type === "custom" && (
              <div className="space-y-4">
                <h3 className="text-lg font-semibold border-b pb-2 flex items-center gap-2">
                  <Award className="w-5 h-5" />
                  Custom Grade Ranges
                </h3>

                {gradeRanges.length > 0 && (
                  <div className="space-y-2">
                    {gradeRanges.map((grade, index) => (
                      <div key={index} className="flex items-center gap-4 p-3 bg-gray-50 rounded-lg">
                        <Badge variant="outline" className="text-lg font-bold">{grade.grade_label}</Badge>
                        <span className="flex-1">
                          {grade.min_score} - {grade.max_score}
                          {grade.grade_point && ` (${grade.grade_point} points)`}
                          {grade.description && ` - ${grade.description}`}
                        </span>
                        <Button
                          type="button"
                          variant="ghost"
                          size="sm"
                          onClick={() => removeGradeRange(index)}
                        >
                          ×
                        </Button>
                      </div>
                    ))}
                  </div>
                )}

                {!showGradeForm ? (
                  <Button
                    type="button"
                    variant="outline"
                    onClick={() => setShowGradeForm(true)}
                    className="w-full"
                  >
                    <Plus className="w-4 h-4 mr-2" />
                    Add Grade Range
                  </Button>
                ) : (
                  <div className="p-4 border rounded-lg space-y-3">
                    <div className="grid grid-cols-2 gap-4">
                      <div className="space-y-2">
                        <Label>Grade Label *</Label>
                        <Input
                          value={gradeFormData.grade_label}
                          onChange={(e) => setGradeFormData({ ...gradeFormData, grade_label: e.target.value })}
                          placeholder="A, B, C..."
                          maxLength={3}
                        />
                      </div>
                      <div className="space-y-2">
                        <Label>Grade Point (Optional)</Label>
                        <Input
                          type="number"
                          step="0.01"
                          value={gradeFormData.grade_point}
                          onChange={(e) => setGradeFormData({ ...gradeFormData, grade_point: e.target.value })}
                          placeholder="4.0"
                        />
                      </div>
                    </div>
                    <div className="grid grid-cols-2 gap-4">
                      <div className="space-y-2">
                        <Label>Min Score *</Label>
                        <Input
                          type="number"
                          step="0.01"
                          value={gradeFormData.min_score}
                          onChange={(e) => setGradeFormData({ ...gradeFormData, min_score: e.target.value })}
                          placeholder="75"
                        />
                      </div>
                      <div className="space-y-2">
                        <Label>Max Score *</Label>
                        <Input
                          type="number"
                          step="0.01"
                          value={gradeFormData.max_score}
                          onChange={(e) => setGradeFormData({ ...gradeFormData, max_score: e.target.value })}
                          placeholder="100"
                        />
                      </div>
                    </div>
                    <div className="space-y-2">
                      <Label>Description (Optional)</Label>
                      <Input
                        value={gradeFormData.description}
                        onChange={(e) => setGradeFormData({ ...gradeFormData, description: e.target.value })}
                        placeholder="Excellent, Good, etc."
                      />
                    </div>
                    <div className="flex gap-2">
                      <Button type="button" onClick={addGradeRange} size="sm">Add</Button>
                      <Button type="button" variant="outline" onClick={() => setShowGradeForm(false)} size="sm">Cancel</Button>
                    </div>
                  </div>
                )}

                {formData.grading_type === "custom" && gradeRanges.length === 0 && (
                  <p className="text-sm text-amber-600">
                    ⚠️ Add at least one grade range for custom grading system
                  </p>
                )}
              </div>
            )}

            {/* Submit Button */}
            <div className="flex gap-4">
              <Button
                type="submit"
                disabled={loading || (formData.grading_type === "custom" && gradeRanges.length === 0)}
                className="flex-1"
              >
                {loading ? "Creating..." : "Create Academic Year"}
              </Button>
              <Button
                type="button"
                variant="outline"
                onClick={() => {
                  setFormData({
                    year_name: "",
                    start_date: "",
                    end_date: "",
                    number_of_terms: "3",
                    exams_per_term: "1",
                    grading_type: "average",
                    term_1_fee: "",
                    term_1_min_payment: "",
                    term_2_fee: "",
                    term_2_min_payment: "",
                    term_3_fee: "",
                    term_3_min_payment: "",
                    result_publication_date: "",
                    auto_calculate_position: true,
                    is_current: false
                  });
                  setGradeRanges([]);
                }}
              >
                Reset
              </Button>
            </div>
          </form>
        </TabsContent>

        <TabsContent value="list" className="mt-6">
          <div className="space-y-4">
            {loading ? (
              <div className="text-center py-8">Loading academic years...</div>
            ) : academicYears.length === 0 ? (
              <div className="text-center py-8 text-gray-500">
                No academic years created yet. Click "Create New" to add one.
              </div>
            ) : (
              academicYears.map((year) => (
                <Card key={year.id}>
                  <CardHeader>
                    <div className="flex items-center justify-between">
                      <div>
                        <CardTitle className="flex items-center gap-2">
                          {year.year_name}
                          {year.is_current && (
                            <Badge className="bg-green-500">Current</Badge>
                          )}
                        </CardTitle>
                        <CardDescription>
                          {year.start_date} to {year.end_date}
                        </CardDescription>
                      </div>
                    </div>
                  </CardHeader>
                  <CardContent>
                    <div className="grid grid-cols-2 md:grid-cols-4 gap-4 text-sm">
                      <div>
                        <span className="font-semibold">Terms:</span> {year.number_of_terms}
                      </div>
                      <div>
                        <span className="font-semibold">Exams/Term:</span> {year.exams_per_term}
                      </div>
                      <div>
                        <span className="font-semibold">Grading:</span>{" "}
                        {year.grading_type === "average" && "Average-Based"}
                        {year.grading_type === "custom" && "Custom"}
                      </div>
                      <div>
                        <span className="font-semibold">Total Exams:</span>{" "}
                        {year.number_of_terms * year.exams_per_term}
                      </div>
                    </div>
                  </CardContent>
                </Card>
              ))
            )}
          </div>
        </TabsContent>
      </Tabs>
        </CardContent>
      </Card>
    </div>
  );
};

export default CreateAcademicYear;
