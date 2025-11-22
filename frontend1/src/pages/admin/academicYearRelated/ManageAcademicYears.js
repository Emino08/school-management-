import React, { useEffect, useState } from "react";
import { useDispatch, useSelector } from "react-redux";
import { useNavigate } from "react-router-dom";
import {
  createAcademicYear,
  getAllAcademicYears,
  setAcademicYear,
  removeAcademicYear
} from "../../../redux/academicYearRelated/academicYearHandle";
import { Button } from "@/components/ui/button";
import { Input } from "@/components/ui/input";
import { Label } from "@/components/ui/label";
import { Card, CardContent, CardHeader, CardTitle, CardDescription } from "@/components/ui/card";
import { Badge } from "@/components/ui/badge";
import { toast } from 'sonner';
import { MdAdd, MdCheck, MdDelete, MdEdit, MdCalendarToday, MdInfo } from "react-icons/md";
import axios from "../../../redux/axiosConfig";
import {
  AlertDialog,
  AlertDialogAction,
  AlertDialogCancel,
  AlertDialogContent,
  AlertDialogDescription,
  AlertDialogFooter,
  AlertDialogHeader,
  AlertDialogTitle,
  AlertDialogTrigger,
} from "@/components/ui/alert-dialog";

const ManageAcademicYears = () => {
  const [showCreateForm, setShowCreateForm] = useState(false);
  const [yearName, setYearName] = useState("");
  const [startDate, setStartDate] = useState("");
  const [endDate, setEndDate] = useState("");
  const dispatch = useDispatch();
  const navigate = useNavigate();

  const academicYearState = useSelector((state) => state.academicYear);
  const {
    academicYearStatus,
    academicYearMessage,
    academicYearLoading,
    academicYearError,
    academicYearData
  } = academicYearState;

  useEffect(() => {
    dispatch(getAllAcademicYears());
  }, [dispatch]);

  useEffect(() => {
    if (academicYearStatus === "succeeded" && academicYearMessage) {
      toast.success('Success', {
        description: academicYearMessage,
      });
      dispatch(getAllAcademicYears());
      setShowCreateForm(false);
      resetForm();
    } else if (academicYearStatus === "failed" && academicYearError) {
      toast.error('Error', {
        description: academicYearError,
      });
    }
  }, [academicYearStatus, academicYearMessage, academicYearError, dispatch]);

  const resetForm = () => {
    setYearName("");
    setStartDate("");
    setEndDate("");
  };

  const handleQuickCreate = () => {
    const currentYear = new Date().getFullYear();
    const nextYear = currentYear + 1;
    const newYearName = `${currentYear}-${nextYear}`;
    const newStartDate = `${currentYear}-09-01`;
    const newEndDate = `${nextYear}-06-30`;

    setYearName(newYearName);
    setStartDate(newStartDate);
    setEndDate(newEndDate);
    setShowCreateForm(true);
  };

  const handleCreateAcademicYear = (e) => {
    e.preventDefault();

    if (!yearName || !startDate || !endDate) {
      toast.error('Validation Error', {
        description: 'Please fill in all fields',
      });
      return;
    }

    if (new Date(startDate) >= new Date(endDate)) {
      toast.error('Validation Error', {
        description: 'End date must be after start date',
      });
      return;
    }

    dispatch(createAcademicYear({
      year_name: yearName,
      start_date: startDate,
      end_date: endDate,
      is_current: false
    }));
  };

  const handleSetCurrent = (yearName) => {
    dispatch(setAcademicYear({ academicYearName: yearName }));
    toast.success('Academic Year Set', {
      description: `${yearName} is now the current academic year`,
    });
  };

  const handleDelete = (id) => {
    dispatch(removeAcademicYear(id, "AcademicYear"));
  };

  const academicYears = Array.isArray(academicYearData) ? academicYearData : [];
  
  // Helper function to check if year is current (handles different data types)
  const isCurrentYear = (year) => {
    // Use loose comparison to catch both 1 and "1"
    return year.is_current == 1 || year.is_current === true;
  };
  
  const currentYear = academicYears.find(year => isCurrentYear(year));
  const [newPublicationDate, setNewPublicationDate] = useState("");

  const formatDate = (value) => {
    if (!value) return 'Not set';
    const d = new Date(value);
    if (Number.isNaN(d.getTime())) return value;
    return d.toLocaleDateString();
  };

  useEffect(() => {
    if (currentYear?.result_publication_date) {
      const d = new Date(currentYear.result_publication_date);
      if (!Number.isNaN(d.getTime())) {
        const tzOffset = d.getTimezoneOffset() * 60000;
        const local = new Date(d.getTime() - tzOffset).toISOString().slice(0, 10);
        setNewPublicationDate(local);
      }
    } else {
      setNewPublicationDate("");
    }
  }, [currentYear]);

  const updatePublicationDate = async () => {
    if (!currentYear?.id) return;
    if (!newPublicationDate) {
      toast.error("Set a publication date before saving.");
      return;
    }
    try {
      await axios.put(
        `${import.meta.env.VITE_API_BASE_URL}/academic-years/${currentYear.id}`,
        { result_publication_date: newPublicationDate }
      );
      toast.success("Result publication date updated.");
      dispatch(getAllAcademicYears());
    } catch (error) {
      toast.error(error?.response?.data?.message || "Failed to update publication date");
    }
  };

  return (
    <div className="space-y-6">
      {/* Header Section */}
      <div className="flex items-center justify-between">
        <div>
          <h1 className="text-3xl font-bold text-gray-900">Academic Year Management</h1>
          <p className="text-gray-600 mt-1">Manage school academic years and sessions</p>
        </div>
        <Button
          onClick={() => setShowCreateForm(!showCreateForm)}
          className="bg-purple-600 hover:bg-purple-700"
        >
          <MdAdd className="mr-2 h-5 w-5" />
          {showCreateForm ? 'Cancel' : 'New Academic Year'}
        </Button>
      </div>

      {/* Info Banner */}
      <Card className="bg-blue-50 border-blue-200">
        <CardContent className="pt-6">
          <div className="flex items-start gap-3">
            <MdInfo className="h-5 w-5 text-blue-600 mt-0.5" />
            <div className="flex-1">
              <h3 className="font-semibold text-blue-900">How Academic Years Work</h3>
              <p className="text-sm text-blue-700 mt-1">
                Academic years help organize your school's data by session. Set one year as "Current" to
                filter students, classes, and records by that session. This enables student promotion,
                year-over-year reporting, and historical data management.
              </p>
            </div>
          </div>
        </CardContent>
      </Card>

      {/* Current Academic Year Card */}
      {currentYear && (
        <Card className="bg-gradient-to-r from-purple-50 to-blue-50 border-purple-200">
          <CardContent className="pt-6">
            <div className="flex items-center justify-between">
              <div className="flex items-center gap-4">
                <div className="w-12 h-12 bg-purple-600 rounded-full flex items-center justify-center">
                  <MdCalendarToday className="h-6 w-6 text-white" />
                </div>
                <div>
                  <div className="flex items-center gap-2">
                    <h2 className="text-2xl font-bold text-gray-900">{currentYear.year_name}</h2>
                    <Badge className="bg-green-600">Current</Badge>
                  </div>
                  <p className="text-gray-600">
                    {new Date(currentYear.start_date).toLocaleDateString('en-US', {
                      month: 'long',
                      day: 'numeric',
                      year: 'numeric'
                    })} - {new Date(currentYear.end_date).toLocaleDateString('en-US', {
                      month: 'long',
                      day: 'numeric',
                      year: 'numeric'
                    })}
                  </p>
                  <p className="text-sm text-gray-600">
                    Result publication date: {formatDate(currentYear.result_publication_date)}
                  </p>
                  <div className="mt-3 space-y-2">
                    <Label htmlFor="publicationDate">Update result publication date</Label>
                    <div className="flex gap-2 flex-wrap items-center">
                      <Input
                        id="publicationDate"
                        type="date"
                        value={newPublicationDate}
                        onChange={(e) => setNewPublicationDate(e.target.value)}
                        className="max-w-xs"
                      />
                      <Button
                        variant="outline"
                        size="sm"
                        onClick={updatePublicationDate}
                        disabled={academicYearLoading}
                      >
                        Save
                      </Button>
                    </div>
                    <p className="text-xs text-gray-500">
                      Principals can update this date to control when students see their results.
                    </p>
                  </div>
                </div>
              </div>
            </div>
          </CardContent>
        </Card>
      )}

      {/* Create Form */}
      {showCreateForm && (
        <Card>
          <CardHeader>
            <CardTitle>Create New Academic Year</CardTitle>
            <CardDescription>Set up a new academic year for your school</CardDescription>
          </CardHeader>
          <CardContent>
            <form onSubmit={handleCreateAcademicYear} className="space-y-4">
              <div className="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div className="space-y-2">
                  <Label htmlFor="yearName">Academic Year Name *</Label>
                  <Input
                    id="yearName"
                    type="text"
                    placeholder="e.g., 2024-2025"
                    value={yearName}
                    onChange={(e) => setYearName(e.target.value)}
                    required
                  />
                </div>
                <div className="space-y-2">
                  <Label htmlFor="startDate">Start Date *</Label>
                  <Input
                    id="startDate"
                    type="date"
                    value={startDate}
                    onChange={(e) => setStartDate(e.target.value)}
                    required
                  />
                </div>
                <div className="space-y-2">
                  <Label htmlFor="endDate">End Date *</Label>
                  <Input
                    id="endDate"
                    type="date"
                    value={endDate}
                    onChange={(e) => setEndDate(e.target.value)}
                    required
                  />
                </div>
              </div>
              <div className="flex gap-3">
                <Button type="submit" disabled={academicYearLoading}>
                  {academicYearLoading ? 'Creating...' : 'Create Academic Year'}
                </Button>
                <Button type="button" variant="outline" onClick={handleQuickCreate}>
                  Quick Fill (Current Year)
                </Button>
                <Button
                  type="button"
                  variant="ghost"
                  onClick={() => {
                    setShowCreateForm(false);
                    resetForm();
                  }}
                >
                  Cancel
                </Button>
              </div>
            </form>
          </CardContent>
        </Card>
      )}

      {/* Academic Years List */}
      <Card>
        <CardHeader>
          <CardTitle>All Academic Years</CardTitle>
          <CardDescription>
            {academicYears.length} academic {academicYears.length === 1 ? 'year' : 'years'} configured
          </CardDescription>
        </CardHeader>
        <CardContent>
          {academicYearLoading && academicYears.length === 0 ? (
            <div className="text-center py-8">
              <p className="text-gray-500">Loading academic years...</p>
            </div>
          ) : academicYears.length === 0 ? (
            <div className="text-center py-12">
              <MdCalendarToday className="h-12 w-12 text-gray-400 mx-auto mb-4" />
              <h3 className="text-lg font-semibold text-gray-900 mb-2">No Academic Years</h3>
              <p className="text-gray-600 mb-4">Create your first academic year to get started</p>
              <Button onClick={() => setShowCreateForm(true)} className="bg-purple-600 hover:bg-purple-700">
                <MdAdd className="mr-2 h-5 w-5" />
                Create Academic Year
              </Button>
            </div>
          ) : (
            <div className="space-y-3">
              {academicYears.map((year) => (
                <div
                  key={year.id}
                  className={`flex items-center justify-between p-4 rounded-lg border ${
                    isCurrentYear(year)
                      ? 'bg-purple-50 border-purple-200'
                      : 'bg-white border-gray-200 hover:border-gray-300'
                  }`}
                >
                  <div className="flex items-center gap-4">
                    <div className={`w-10 h-10 rounded-full flex items-center justify-center ${
                      isCurrentYear(year) ? 'bg-purple-600' : 'bg-gray-200'
                    }`}>
                      <MdCalendarToday className={`h-5 w-5 ${
                        isCurrentYear(year) ? 'text-white' : 'text-gray-600'
                      }`} />
                    </div>
                    <div>
                      <div className="flex items-center gap-2">
                        <h3 className="font-semibold text-gray-900">{year.year_name}</h3>
                        {isCurrentYear(year) && (
                          <Badge className="bg-green-600">
                            <MdCheck className="mr-1 h-3 w-3" />
                            Current
                          </Badge>
                        )}
                        <Badge variant="outline" className="capitalize">
                          {year.status}
                        </Badge>
                      </div>
                      <p className="text-sm text-gray-600">
                        {new Date(year.start_date).toLocaleDateString()} - {new Date(year.end_date).toLocaleDateString()}
                      </p>
                      <p className="text-xs text-gray-500">
                        Result publication date: {formatDate(year.result_publication_date)}
                      </p>
                    </div>
                  </div>
                  <div className="flex items-center gap-2">
                    {!isCurrentYear(year) && (
                      <Button
                        variant="outline"
                        size="sm"
                        onClick={() => handleSetCurrent(year.year_name)}
                        className="border-purple-600 text-purple-600 hover:bg-purple-50"
                      >
                        Set as Current
                      </Button>
                    )}
                    <AlertDialog>
                      <AlertDialogTrigger asChild>
                        <Button
                          variant="ghost"
                          size="icon"
                          className="text-red-600 hover:bg-red-50"
                        >
                          <MdDelete className="h-5 w-5" />
                        </Button>
                      </AlertDialogTrigger>
                      <AlertDialogContent>
                        <AlertDialogHeader>
                          <AlertDialogTitle>Delete Academic Year?</AlertDialogTitle>
                          <AlertDialogDescription>
                            This will permanently delete the academic year "{year.year_name}".
                            This action cannot be undone.
                          </AlertDialogDescription>
                        </AlertDialogHeader>
                        <AlertDialogFooter>
                          <AlertDialogCancel>Cancel</AlertDialogCancel>
                          <AlertDialogAction
                            onClick={() => handleDelete(year.id)}
                            className="bg-red-600 hover:bg-red-700"
                          >
                            Delete
                          </AlertDialogAction>
                        </AlertDialogFooter>
                      </AlertDialogContent>
                    </AlertDialog>
                  </div>
                </div>
              ))}
            </div>
          )}
        </CardContent>
      </Card>
    </div>
  );
};

export default ManageAcademicYears;
