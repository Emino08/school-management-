import React, { useState, useEffect } from "react";
import { useSelector } from "react-redux";
import axios from "@/redux/axiosConfig";
import { toast } from "sonner";
import { Card, CardHeader, CardTitle, CardDescription, CardContent } from "../../../components/ui/card";
import { Tabs, TabsContent, TabsList, TabsTrigger } from "../../../components/ui/tabs";
import { Label } from "../../../components/ui/label";
import { Input } from "../../../components/ui/input";
import { Button } from "../../../components/ui/button";
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from "../../../components/ui/select";
import { Badge } from "../../../components/ui/badge";
import {
  Table,
  TableBody,
  TableCell,
  TableHead,
  TableHeader,
  TableRow,
} from "../../../components/ui/table";
import {
  DollarSign,
  TrendingUp,
  Users,
  Calendar,
  CreditCard,
  CheckCircle,
  AlertCircle,
  XCircle,
  Download,
  Search,
  Filter,
  Plus,
  Receipt
} from "lucide-react";

const EnhancedFeesManagement = () => {
  const { currentUser } = useSelector((state) => state.user);
  const [activeTab, setActiveTab] = useState("overview");
  const [loading, setLoading] = useState(false);
  
  // Data states
  const [academicYears, setAcademicYears] = useState([]);
  const [selectedYear, setSelectedYear] = useState("");
  const [classes, setClasses] = useState([]);
  const [students, setStudents] = useState([]);
  const [payments, setPayments] = useState([]);
  const [feeStructures, setFeeStructures] = useState([]);
  const [stats, setStats] = useState({
    total_expected: 0,
    total_collected: 0,
    total_pending: 0,
    fully_paid_count: 0,
    partial_paid_count: 0,
    unpaid_count: 0,
    term_1_collected: 0,
    term_2_collected: 0,
    term_3_collected: 0
  });

  // Form states
  const [paymentForm, setPaymentForm] = useState({
    student_id: undefined,
    term: "1",
    fee_structure_id: undefined,
    amount: "",
    payment_date: new Date().toISOString().split('T')[0],
    payment_method: "cash",
    reference_number: "",
    notes: ""
  });

  // Filter states
  const [filterClass, setFilterClass] = useState("all");
  const [filterTerm, setFilterTerm] = useState("all");
  const [filterStatus, setFilterStatus] = useState("all");
  const [searchQuery, setSearchQuery] = useState("");

  const API_URL = import.meta.env.VITE_API_BASE_URL || process.env.REACT_APP_BASE_URL || "";

  useEffect(() => {
    fetchAcademicYears();
    fetchClasses();
    fetchStudents(); // Load students on component mount
  }, []);

  useEffect(() => {
    if (selectedYear) {
      fetchStats();
      fetchAllPayments();
      fetchStudents(); // Reload students when year changes
      fetchFeeStructures(); // Load fee structures
    }
  }, [selectedYear]);

  const fetchAcademicYears = async () => {
    try {
      const response = await axios.get(`${API_URL}/academic-years`);
      if (response.data.success) {
        const years = response.data.academic_years || [];
        setAcademicYears(years);
        const current = years.find(y => y.is_current);
        if (current) setSelectedYear(current.id.toString());
      }
    } catch (error) {
      console.error("Error fetching academic years:", error);
    }
  };

  const fetchClasses = async () => {
    try {
      const response = await axios.get(`${API_URL}/classes`);
      if (response.data.success) {
        setClasses(response.data.classes || []);
      }
    } catch (error) {
      console.error("Error fetching classes:", error);
    }
  };

  const fetchStudents = async () => {
    try {
      const response = await axios.get(`${API_URL}/students`);

      console.log('Students API Response:', response.data); // Debug log

      if (response.data.success) {
        const studentsList = response.data.students || [];
        console.log('Students loaded:', studentsList.length); // Debug log

        // Debug: log first student object to see its exact structure
        if (studentsList.length > 0) {
          console.log('First student object:', studentsList[0]);
          console.log('Student properties:', Object.keys(studentsList[0]));
        }

        setStudents(studentsList);

        if (studentsList.length === 0) {
          toast.info('No students found. Please add students first.');
        }
      } else {
        toast.error('Failed to load students');
        console.error('API returned success=false:', response.data);
      }
    } catch (error) {
      console.error("Error fetching students:", error);
      toast.error('Failed to load students: ' + (error.response?.data?.message || error.message));
    }
  };

  const fetchFeeStructures = async () => {
    try {
      const response = await axios.get(
        `${API_URL}/fee-structures?academic_year_id=${selectedYear}`
      );

      console.log('Fee Structures API Response:', response.data); // Debug log

      if (response.data.success) {
        const structures = response.data.feeStructures || [];
        console.log('Fee structures loaded:', structures.length); // Debug log
        setFeeStructures(structures);

        if (structures.length === 0) {
          toast.info('No fee structures found. Please add fee structures first.');
        }
      } else {
        console.error('API returned success=false:', response.data);
      }
    } catch (error) {
      console.error("Error fetching fee structures:", error);
      toast.error('Failed to load fee structures: ' + (error.response?.data?.message || error.message));
    }
  };

  const fetchStats = async () => {
    setLoading(true);
    try {
      const response = await axios.get(
        `${API_URL}/fees/stats?academic_year_id=${selectedYear}`
      );
      if (response.data.success) {
        setStats(response.data.stats || stats);
      }
    } catch (error) {
      console.error("Error fetching stats:", error);
    } finally {
      setLoading(false);
    }
  };

  const fetchAllPayments = async () => {
    try {
      const response = await axios.get(
        `${API_URL}/fees/all?academic_year_id=${selectedYear}`
      );
      if (response.data.success) {
        setPayments(response.data.payments || []);
      }
    } catch (error) {
      console.error("Error fetching payments:", error);
    }
  };

  const handlePaymentSubmit = async (e) => {
    e.preventDefault();
    setLoading(true);

    try {
      // Prepare payload, removing undefined values
      const payload = {
        student_id: paymentForm.student_id,
        term: paymentForm.term,
        academic_year_id: selectedYear,
        amount: parseFloat(paymentForm.amount),
        payment_date: paymentForm.payment_date,
        payment_method: paymentForm.payment_method,
        reference_number: paymentForm.reference_number,
        notes: paymentForm.notes
      };

      // Add fee_structure_id only if it's defined
      if (paymentForm.fee_structure_id) {
        payload.fee_structure_id = paymentForm.fee_structure_id;
      }

      const response = await axios.post(
        `${API_URL}/fees`,
        payload
      );

      if (response.data.success) {
        toast.success("Payment recorded successfully!");
        setPaymentForm({
          student_id: undefined,
          term: "1",
          fee_structure_id: undefined,
          amount: "",
          payment_date: new Date().toISOString().split('T')[0],
          payment_method: "cash",
          reference_number: "",
          notes: ""
        });
        fetchStats();
        fetchAllPayments();
        setActiveTab("payments");
      }
    } catch (error) {
      toast.error(error.response?.data?.message || "Failed to record payment");
    } finally {
      setLoading(false);
    }
  };

  const getPaymentStatus = (student, term) => {
    const yearData = academicYears.find(y => y.id.toString() === selectedYear);
    if (!yearData) return { status: "unknown", percentage: 0 };

    const termFee = yearData[`term_${term}_fee`] || 0;
    const minPayment = yearData[`term_${term}_min_payment`] || termFee;

    // Map numeric term to database ENUM format
    const termMap = { '1': '1st Term', '2': '2nd Term', '3': 'Full Year' };
    const termEnum = termMap[term.toString()] || term;

    const studentPayments = payments.filter(
      p => p.student_id === student.id && (p.term === term || p.term === termEnum || p.term === term.toString())
    );
    const totalPaid = studentPayments.reduce((sum, p) => sum + parseFloat(p.amount), 0);

    if (totalPaid >= termFee) {
      return { status: "fully_paid", percentage: 100, paid: totalPaid, required: termFee };
    } else if (totalPaid >= minPayment) {
      return { status: "qualified", percentage: (totalPaid / termFee) * 100, paid: totalPaid, required: termFee };
    } else if (totalPaid > 0) {
      return { status: "partial", percentage: (totalPaid / termFee) * 100, paid: totalPaid, required: termFee };
    } else {
      return { status: "unpaid", percentage: 0, paid: 0, required: termFee };
    }
  };

  const getStatusBadge = (status) => {
    switch (status) {
      case "fully_paid":
        return <Badge className="bg-green-500"><CheckCircle className="w-3 h-3 mr-1" />Fully Paid</Badge>;
      case "qualified":
        return <Badge className="bg-blue-500"><CheckCircle className="w-3 h-3 mr-1" />Qualified</Badge>;
      case "partial":
        return <Badge className="bg-amber-500"><AlertCircle className="w-3 h-3 mr-1" />Partial</Badge>;
      case "unpaid":
        return <Badge className="bg-red-500"><XCircle className="w-3 h-3 mr-1" />Unpaid</Badge>;
      default:
        return <Badge variant="secondary">Unknown</Badge>;
    }
  };

  const formatTermDisplay = (term) => {
    // If term is already in format like "1st Term", return as is
    if (typeof term === 'string' && term.includes('Term')) {
      return term;
    }
    // Otherwise format numeric term
    return `Term ${term}`;
  };

  const exportToCSV = () => {
    if (payments.length === 0) {
      toast.error("No payments to export");
      return;
    }

    const headers = ["Date", "Student", "Class", "Term", "Amount", "Method", "Reference", "Status"];
    const rows = payments.map(p => [
      p.payment_date,
      p.student_name,
      p.class_name,
      formatTermDisplay(p.term),
      p.amount,
      p.payment_method,
      p.reference_number || "N/A",
      p.status
    ]);

    const csv = [headers, ...rows].map(row => row.join(",")).join("\n");
    const blob = new Blob([csv], { type: "text/csv" });
    const url = URL.createObjectURL(blob);
    const link = document.createElement("a");
    link.href = url;
    link.download = `fees_payments_${new Date().toISOString().split('T')[0]}.csv`;
    link.click();
    URL.revokeObjectURL(url);
    toast.success("Payments exported successfully");
  };

  const collectionRate = stats.total_expected > 0 
    ? ((stats.total_collected / stats.total_expected) * 100).toFixed(1)
    : 0;

  const filteredPayments = payments.filter(payment => {
    if (filterClass !== "all" && payment.class_id?.toString() !== filterClass) return false;
    if (filterTerm !== "all" && payment.term?.toString() !== filterTerm) return false;
    if (filterStatus !== "all" && payment.status !== filterStatus) return false;
    if (searchQuery) {
      const search = searchQuery.toLowerCase();
      return payment.student_name?.toLowerCase().includes(search) ||
             payment.reference_number?.toLowerCase().includes(search);
    }
    return true;
  });

  const selectedYearData = academicYears.find(y => y.id.toString() === selectedYear);

  return (
    <div className="p-6 max-w-7xl mx-auto space-y-6">
      {/* Header */}
      <div className="flex items-center justify-between">
        <div>
          <h1 className="text-3xl font-bold flex items-center gap-3">
            <div className="p-2 bg-gradient-to-br from-green-500 to-emerald-600 rounded-lg">
              <DollarSign className="w-8 h-8 text-white" />
            </div>
            Fees Management
          </h1>
          <p className="text-gray-600 mt-1">Complete fees collection and tracking system</p>
        </div>
        
        <div className="w-64">
          <Label>Academic Year</Label>
          <Select value={selectedYear} onValueChange={setSelectedYear}>
            <SelectTrigger>
              <SelectValue placeholder="Select Year" />
            </SelectTrigger>
            <SelectContent>
              {academicYears.map((year) => (
                <SelectItem key={year.id} value={year.id.toString()}>
                  {year.year_name} {year.is_current && "⭐"}
                </SelectItem>
              ))}
            </SelectContent>
          </Select>
        </div>
      </div>

      {selectedYear && (
        <>
          {/* Statistics Cards */}
          <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
            <Card className="bg-gradient-to-br from-blue-50 to-blue-100 border-blue-200">
              <CardContent className="pt-6">
                <div className="flex items-center justify-between">
                  <div>
                    <p className="text-sm font-medium text-blue-600">Total Expected</p>
                    <h3 className="text-2xl font-bold text-blue-900">
                      {(Number(stats?.total_expected ?? 0)).toLocaleString()}
                    </h3>
                  </div>
                  <div className="p-3 bg-blue-500 rounded-lg">
                    <TrendingUp className="w-6 h-6 text-white" />
                  </div>
                </div>
              </CardContent>
            </Card>

            <Card className="bg-gradient-to-br from-green-50 to-green-100 border-green-200">
              <CardContent className="pt-6">
                <div className="flex items-center justify-between">
                  <div>
                    <p className="text-sm font-medium text-green-600">Total Collected</p>
                    <h3 className="text-2xl font-bold text-green-900">
                      {(Number(stats?.total_collected ?? 0)).toLocaleString()}
                    </h3>
                    <p className="text-xs text-green-600 mt-1">{collectionRate}% collected</p>
                  </div>
                  <div className="p-3 bg-green-500 rounded-lg">
                    <CheckCircle className="w-6 h-6 text-white" />
                  </div>
                </div>
              </CardContent>
            </Card>

            <Card className="bg-gradient-to-br from-amber-50 to-amber-100 border-amber-200">
              <CardContent className="pt-6">
                <div className="flex items-center justify-between">
                  <div>
                    <p className="text-sm font-medium text-amber-600">Pending Amount</p>
                    <h3 className="text-2xl font-bold text-amber-900">
                      {(Number(stats?.total_pending ?? 0)).toLocaleString()}
                    </h3>
                  </div>
                  <div className="p-3 bg-amber-500 rounded-lg">
                    <AlertCircle className="w-6 h-6 text-white" />
                  </div>
                </div>
              </CardContent>
            </Card>

            <Card className="bg-gradient-to-br from-purple-50 to-purple-100 border-purple-200">
              <CardContent className="pt-6">
                <div className="flex items-center justify-between">
                  <div>
                    <p className="text-sm font-medium text-purple-600">Students Tracking</p>
                    <div className="flex items-center gap-2 mt-1">
                      <span className="text-sm text-green-600">✓ {stats.fully_paid_count}</span>
                      <span className="text-sm text-amber-600">⚠ {stats.partial_paid_count}</span>
                      <span className="text-sm text-red-600">✗ {stats.unpaid_count}</span>
                    </div>
                  </div>
                  <div className="p-3 bg-purple-500 rounded-lg">
                    <Users className="w-6 h-6 text-white" />
                  </div>
                </div>
              </CardContent>
            </Card>
          </div>

          {/* Term Breakdown */}
          {selectedYearData && (
            <Card>
              <CardHeader>
                <CardTitle className="flex items-center gap-2">
                  <Calendar className="w-5 h-5" />
                  Term-wise Collection
                </CardTitle>
              </CardHeader>
              <CardContent>
                <div className="grid grid-cols-1 md:grid-cols-3 gap-4">
                  {[1, 2, selectedYearData.number_of_terms === 3 ? 3 : null].filter(Boolean).map(term => {
                    const termFee = selectedYearData[`term_${term}_fee`] || 0;
                    const minPayment = selectedYearData[`term_${term}_min_payment`] || termFee;
                    const collected = stats[`term_${term}_collected`] || 0;
                    const percentage = termFee > 0 ? ((collected / termFee) * 100).toFixed(1) : 0;

                    return (
                      <div key={term} className="p-4 border rounded-lg bg-gradient-to-br from-gray-50 to-gray-100">
                        <div className="flex items-center justify-between mb-2">
                          <h4 className="font-semibold text-gray-900">Term {term}</h4>
                          <Badge variant="outline">{percentage}%</Badge>
                        </div>
                        <div className="space-y-2 text-sm">
                          <div className="flex justify-between">
                            <span className="text-gray-600">Expected:</span>
                            <span className="font-semibold">{(Number(termFee ?? 0)).toLocaleString()}</span>
                          </div>
                          <div className="flex justify-between">
                            <span className="text-gray-600">Min Payment:</span>
                            <span className="font-semibold text-blue-600">{(Number(minPayment ?? 0)).toLocaleString()}</span>
                          </div>
                          <div className="flex justify-between">
                            <span className="text-gray-600">Collected:</span>
                            <span className="font-semibold text-green-600">{(Number(collected ?? 0)).toLocaleString()}</span>
                          </div>
                          <div className="w-full bg-gray-200 rounded-full h-2 mt-2">
                            <div
                              className="bg-gradient-to-r from-green-500 to-emerald-600 h-2 rounded-full transition-all"
                              style={{ width: `${Math.min(percentage, 100)}%` }}
                            ></div>
                          </div>
                        </div>
                      </div>
                    );
                  })}
                </div>
              </CardContent>
            </Card>
          )}

          {/* Main Tabs */}
          <Card>
            <CardContent className="pt-6">
              <Tabs value={activeTab} onValueChange={setActiveTab}>
                <TabsList className="grid w-full grid-cols-4">
                  <TabsTrigger value="overview" className="gap-2">
                    <TrendingUp className="w-4 h-4" />
                    Overview
                  </TabsTrigger>
                  <TabsTrigger value="record" className="gap-2">
                    <Plus className="w-4 h-4" />
                    Record Payment
                  </TabsTrigger>
                  <TabsTrigger value="payments" className="gap-2">
                    <Receipt className="w-4 h-4" />
                    All Payments ({payments.length})
                  </TabsTrigger>
                  <TabsTrigger value="students" className="gap-2">
                    <Users className="w-4 h-4" />
                    Student Status
                  </TabsTrigger>
                </TabsList>

                {/* Overview Tab */}
                <TabsContent value="overview" className="space-y-4 mt-6">
                  <div className="text-center py-8">
                    <p className="text-gray-600">Overview statistics are displayed in the cards above.</p>
                    <p className="text-sm text-gray-500 mt-2">Use other tabs to record payments or view details.</p>
                  </div>
                </TabsContent>

                {/* Record Payment Tab */}
                <TabsContent value="record" className="mt-6">
                  <form onSubmit={handlePaymentSubmit} className="space-y-6">
                    <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                      <div className="space-y-2">
                        <div className="flex items-center justify-between">
                          <Label>Student *</Label>
                          <Button
                            type="button"
                            variant="ghost"
                            size="sm"
                            onClick={fetchStudents}
                            className="h-6 px-2 text-xs"
                          >
                            <Search className="w-3 h-3 mr-1" />
                            Refresh
                          </Button>
                        </div>
                        <Select
                          value={paymentForm.student_id}
                          onValueChange={(value) => setPaymentForm({ ...paymentForm, student_id: value })}
                        >
                          <SelectTrigger>
                            <SelectValue placeholder={students.length === 0 ? "No students found" : "Select Student"} />
                          </SelectTrigger>
                          <SelectContent>
                            {students.length === 0 ? (
                              <div className="px-4 py-2 text-sm text-gray-500 text-center">
                                No students available
                              </div>
                            ) : (
                              students
                                .filter(student => student && student.id) // Filter out students without valid IDs
                                .map((student) => {
                                  // Build student display with fallbacks - handle empty strings too
                                  const displayName = (student.name && student.name.trim()) ||
                                                     (student.student_name && student.student_name.trim()) ||
                                                     'Unknown Student';
                                  const displayId = (student.id_number && student.id_number.trim()) ||
                                                   (student.roll_number && student.roll_number.trim()) ||
                                                   (student.idNumber && student.idNumber.trim()) ||
                                                   '';
                                  const displayClass = (student.class_name && student.class_name.trim()) ||
                                                      (student.sclassName?.sclassName && student.sclassName.sclassName.trim()) ||
                                                      '';

                                  // Build complete display text
                                  let displayText = displayName;
                                  if (displayId) {
                                    displayText += ` (${displayId})`;
                                  }
                                  if (displayClass) {
                                    displayText += ` - ${displayClass}`;
                                  }

                                  // Ensure we have a valid ID
                                  const studentIdValue = student.id ? student.id.toString() : `temp-${Math.random()}`;

                                  // Debug log
                                  console.log(`Rendering student ${student.id}:`, {
                                    name: student.name,
                                    id_number: student.id_number,
                                    class_name: student.class_name,
                                    displayText,
                                    valueUsed: studentIdValue
                                  });

                                  return (
                                    <SelectItem key={student.id} value={studentIdValue}>
                                      {displayText}
                                    </SelectItem>
                                  );
                                })
                            )}
                          </SelectContent>
                        </Select>
                        {students.length === 0 && (
                          <p className="text-xs text-amber-600">
                            No students found for the selected academic year. Please ensure students are enrolled.
                          </p>
                        )}
                        {students.length > 0 && (
                          <p className="text-xs text-gray-500">
                            {students.length} student{students.length !== 1 ? 's' : ''} available
                          </p>
                        )}
                      </div>

                      <div className="space-y-2">
                        <Label>Term *</Label>
                        <Select
                          value={paymentForm.term}
                          onValueChange={(value) => setPaymentForm({ ...paymentForm, term: value })}
                        >
                          <SelectTrigger>
                            <SelectValue />
                          </SelectTrigger>
                          <SelectContent>
                            <SelectItem value="1">Term 1</SelectItem>
                            <SelectItem value="2">Term 2</SelectItem>
                            {selectedYearData?.number_of_terms === 3 && (
                              <SelectItem value="3">Term 3</SelectItem>
                            )}
                          </SelectContent>
                        </Select>
                      </div>

                      <div className="space-y-2">
                        <div className="flex items-center justify-between">
                          <Label>Fee Type</Label>
                          <Button
                            type="button"
                            variant="ghost"
                            size="sm"
                            onClick={fetchFeeStructures}
                            className="h-6 px-2 text-xs"
                          >
                            <Search className="w-3 h-3 mr-1" />
                            Refresh
                          </Button>
                        </div>
                        <Select
                          value={paymentForm.fee_structure_id}
                          onValueChange={(value) => {
                            const selectedFee = feeStructures.find(f => f.id.toString() === value);
                            setPaymentForm({
                              ...paymentForm,
                              fee_structure_id: value,
                              amount: selectedFee ? selectedFee.amount : paymentForm.amount
                            });
                          }}
                        >
                          <SelectTrigger>
                            <SelectValue placeholder={feeStructures.length === 0 ? "No fee types found" : "Select Fee Type (Optional)"} />
                          </SelectTrigger>
                          <SelectContent>
                            {feeStructures.length === 0 ? (
                              <div className="px-4 py-2 text-sm text-gray-500 text-center">
                                No fee structures available.
                              </div>
                            ) : (
                              feeStructures.map((fee) => (
                                <SelectItem key={fee.id} value={fee.id.toString()}>
                                  {fee.fee_name} - SLE {parseFloat(fee.amount).toLocaleString()}
                                </SelectItem>
                              ))
                            )}
                          </SelectContent>
                        </Select>
                        {feeStructures.length > 0 && (
                          <p className="text-xs text-gray-500">
                            {feeStructures.length} fee type{feeStructures.length !== 1 ? 's' : ''} available
                          </p>
                        )}
                      </div>

                      <div className="space-y-2">
                        <Label>Amount *</Label>
                        <Input
                          type="number"
                          step="0.01"
                          value={paymentForm.amount}
                          onChange={(e) => setPaymentForm({ ...paymentForm, amount: e.target.value })}
                          placeholder="0.00"
                          required
                        />
                        <p className="text-xs text-gray-500">
                          Select a fee type to auto-fill the amount, or enter manually
                        </p>
                      </div>

                      <div className="space-y-2">
                        <Label>Payment Date *</Label>
                        <Input
                          type="date"
                          value={paymentForm.payment_date}
                          onChange={(e) => setPaymentForm({ ...paymentForm, payment_date: e.target.value })}
                          required
                        />
                      </div>

                      <div className="space-y-2">
                        <Label>Payment Method *</Label>
                        <Select
                          value={paymentForm.payment_method}
                          onValueChange={(value) => setPaymentForm({ ...paymentForm, payment_method: value })}
                        >
                          <SelectTrigger>
                            <SelectValue />
                          </SelectTrigger>
                          <SelectContent>
                            <SelectItem value="cash">Cash</SelectItem>
                            <SelectItem value="bank_transfer">Bank Transfer</SelectItem>
                            <SelectItem value="cheque">Cheque</SelectItem>
                            <SelectItem value="mobile_money">Mobile Money</SelectItem>
                            <SelectItem value="card">Card Payment</SelectItem>
                          </SelectContent>
                        </Select>
                      </div>

                      <div className="space-y-2">
                        <Label>Reference Number</Label>
                        <Input
                          value={paymentForm.reference_number}
                          onChange={(e) => setPaymentForm({ ...paymentForm, reference_number: e.target.value })}
                          placeholder="Transaction ID / Receipt No"
                        />
                      </div>
                    </div>

                    <div className="space-y-2">
                      <Label>Notes</Label>
                      <Input
                        value={paymentForm.notes}
                        onChange={(e) => setPaymentForm({ ...paymentForm, notes: e.target.value })}
                        placeholder="Additional notes..."
                      />
                    </div>

                    <div className="flex gap-4">
                      <Button type="submit" disabled={loading} className="flex-1">
                        <CreditCard className="w-4 h-4 mr-2" />
                        {loading ? "Recording..." : "Record Payment"}
                      </Button>
                      <Button
                        type="button"
                        variant="outline"
                        onClick={() => setPaymentForm({
                          student_id: "",
                          term: "1",
                          fee_structure_id: "",
                          amount: "",
                          payment_date: new Date().toISOString().split('T')[0],
                          payment_method: "cash",
                          reference_number: "",
                          notes: ""
                        })}
                      >
                        Reset
                      </Button>
                    </div>
                  </form>
                </TabsContent>

                {/* All Payments Tab */}
                <TabsContent value="payments" className="space-y-4 mt-6">
                  <div className="flex gap-4 items-end">
                    <div className="flex-1 space-y-2">
                      <Label>Search</Label>
                      <div className="relative">
                        <Search className="absolute left-3 top-3 h-4 w-4 text-gray-400" />
                        <Input
                          value={searchQuery}
                          onChange={(e) => setSearchQuery(e.target.value)}
                          placeholder="Search by student name or reference..."
                          className="pl-10"
                        />
                      </div>
                    </div>

                    <div className="w-40 space-y-2">
                      <Label>Class</Label>
                      <Select value={filterClass} onValueChange={setFilterClass}>
                        <SelectTrigger>
                          <SelectValue />
                        </SelectTrigger>
                        <SelectContent>
                          <SelectItem value="all">All Classes</SelectItem>
                          {classes.map((cls) => (
                            <SelectItem key={cls.id} value={cls.id.toString()}>
                              {cls.class_name}
                            </SelectItem>
                          ))}
                        </SelectContent>
                      </Select>
                    </div>

                    <div className="w-32 space-y-2">
                      <Label>Term</Label>
                      <Select value={filterTerm} onValueChange={setFilterTerm}>
                        <SelectTrigger>
                          <SelectValue />
                        </SelectTrigger>
                        <SelectContent>
                          <SelectItem value="all">All Terms</SelectItem>
                          <SelectItem value="1">Term 1</SelectItem>
                          <SelectItem value="2">Term 2</SelectItem>
                          <SelectItem value="3">Term 3</SelectItem>
                        </SelectContent>
                      </Select>
                    </div>

                    <Button onClick={exportToCSV} variant="outline" className="gap-2">
                      <Download className="w-4 h-4" />
                      Export
                    </Button>
                  </div>

                  <div className="rounded-lg border">
                    <Table>
                      <TableHeader>
                        <TableRow>
                          <TableHead>Date</TableHead>
                          <TableHead>Student</TableHead>
                          <TableHead>Class</TableHead>
                          <TableHead>Term</TableHead>
                          <TableHead className="text-right">Amount</TableHead>
                          <TableHead>Method</TableHead>
                          <TableHead>Reference</TableHead>
                        </TableRow>
                      </TableHeader>
                      <TableBody>
                        {filteredPayments.length === 0 ? (
                          <TableRow>
                            <TableCell colSpan={7} className="text-center py-8 text-gray-500">
                              No payments found
                            </TableCell>
                          </TableRow>
                        ) : (
                          filteredPayments.map((payment) => (
                            <TableRow key={payment.id}>
                              <TableCell>{payment.payment_date}</TableCell>
                              <TableCell className="font-medium">{payment.student_name}</TableCell>
                              <TableCell>{payment.class_name}</TableCell>
                              <TableCell>
                                <Badge variant="outline">{formatTermDisplay(payment.term)}</Badge>
                              </TableCell>
                              <TableCell className="text-right font-semibold text-green-600">
                                SLE {parseFloat(payment.amount).toLocaleString()}
                              </TableCell>
                              <TableCell className="capitalize">{payment.payment_method?.replace('_', ' ')}</TableCell>
                              <TableCell className="text-sm text-gray-600">{payment.reference_number || "—"}</TableCell>
                            </TableRow>
                          ))
                        )}
                      </TableBody>
                    </Table>
                  </div>
                </TabsContent>

                {/* Student Status Tab */}
                <TabsContent value="students" className="mt-6">
                  <div className="space-y-4">
                    <p className="text-sm text-gray-600">
                      View payment status for each student across all terms
                    </p>
                    
                    <div className="rounded-lg border">
                      <Table>
                        <TableHeader>
                          <TableRow>
                            <TableHead>Student</TableHead>
                            <TableHead>Class</TableHead>
                            <TableHead>Term 1</TableHead>
                            <TableHead>Term 2</TableHead>
                            {selectedYearData?.number_of_terms === 3 && (
                              <TableHead>Term 3</TableHead>
                            )}
                            <TableHead className="text-right">Total Paid</TableHead>
                          </TableRow>
                        </TableHeader>
                        <TableBody>
                          {students.length === 0 ? (
                            <TableRow>
                              <TableCell colSpan={6} className="text-center py-8 text-gray-500">
                                No students found
                              </TableCell>
                            </TableRow>
                          ) : (
                            students.map((student) => {
                              const term1Status = getPaymentStatus(student, "1");
                              const term2Status = getPaymentStatus(student, "2");
                              const term3Status = selectedYearData?.number_of_terms === 3 
                                ? getPaymentStatus(student, "3") 
                                : null;
                              
                              const totalPaid = term1Status.paid + term2Status.paid + (term3Status?.paid || 0);

                              return (
                                <TableRow key={student.id}>
                                  <TableCell className="font-medium">{student.name}</TableCell>
                                  <TableCell>{student.class_name}</TableCell>
                                  <TableCell>
                                    <div className="space-y-1">
                                      {getStatusBadge(term1Status.status)}
                                      <p className="text-xs text-gray-600">
                                        SLE {term1Status.paid.toLocaleString()} / SLE {term1Status.required.toLocaleString()}
                                      </p>
                                    </div>
                                  </TableCell>
                                  <TableCell>
                                    <div className="space-y-1">
                                      {getStatusBadge(term2Status.status)}
                                      <p className="text-xs text-gray-600">
                                        SLE {term2Status.paid.toLocaleString()} / SLE {term2Status.required.toLocaleString()}
                                      </p>
                                    </div>
                                  </TableCell>
                                  {term3Status && (
                                    <TableCell>
                                      <div className="space-y-1">
                                        {getStatusBadge(term3Status.status)}
                                        <p className="text-xs text-gray-600">
                                          SLE {term3Status.paid.toLocaleString()} / SLE {term3Status.required.toLocaleString()}
                                        </p>
                                      </div>
                                    </TableCell>
                                  )}
                                  <TableCell className="text-right font-semibold text-green-600">
                                    SLE {totalPaid.toLocaleString()}
                                  </TableCell>
                                </TableRow>
                              );
                            })
                          )}
                        </TableBody>
                      </Table>
                    </div>
                  </div>
                </TabsContent>
              </Tabs>
            </CardContent>
          </Card>
        </>
      )}

      {!selectedYear && (
        <Card>
          <CardContent className="py-12">
            <div className="text-center">
              <Calendar className="w-12 h-12 text-gray-400 mx-auto mb-4" />
              <h3 className="text-lg font-semibold text-gray-900 mb-2">No Academic Year Selected</h3>
              <p className="text-gray-600">Please select an academic year to manage fees</p>
            </div>
          </CardContent>
        </Card>
      )}
    </div>
  );
};

export default EnhancedFeesManagement;

