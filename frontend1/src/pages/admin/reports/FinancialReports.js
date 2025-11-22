import React, { useState, useEffect } from 'react';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import axios from '@/redux/axiosConfig';
import { toast } from 'sonner';

const FinancialReports = () => {
  const [financialOverview, setFinancialOverview] = useState(null);
  const [feeCollection, setFeeCollection] = useState([]);
  const [loading, setLoading] = useState(true);
  const [academicYears, setAcademicYears] = useState([]);
  const [selectedYear, setSelectedYear] = useState('');

  useEffect(() => {
    // Load years first, then load financial data
    fetchAcademicYears();
  }, []);

  useEffect(() => {
    if (selectedYear) {
      fetchFinancialData();
    }
  }, [selectedYear]);

  const fetchAcademicYears = async () => {
    try {
      const res = await axios.get(`${import.meta.env.VITE_API_BASE_URL}/academic-years`);
      if (res.data.success) {
        const years = res.data.academic_years || res.data.academicYears || [];
        setAcademicYears(years);
        const current = years.find((y) => y.is_current);
        if (current) setSelectedYear(String(current.id));
        else if (years.length > 0) setSelectedYear(String(years[0].id));
      }
    } catch (e) {
      console.error('Error fetching academic years:', e);
      toast.error('Failed to load academic years');
      setLoading(false);
    }
  };

  const fetchFinancialData = async () => {
    try {
      const params = { academic_year_id: selectedYear };

      const [overviewRes, collectionRes] = await Promise.all([
        axios.get(`${import.meta.env.VITE_API_BASE_URL}/reports/financial-overview`, { params }),
        axios.get(`${import.meta.env.VITE_API_BASE_URL}/reports/fee-collection`, { params })
      ]);

      if (overviewRes.data.success) {
        const ov = overviewRes.data.data || overviewRes.data.overview || null;
        setFinancialOverview(ov);
      }
      if (collectionRes.data.success) {
        const coll = collectionRes.data.data || collectionRes.data.collection || [];
        setFeeCollection(coll);
      }

      setLoading(false);
    } catch (error) {
      console.error('Error fetching financial data:', error);
      toast.error(error.response?.data?.message || 'Failed to load financial data');
      setLoading(false);
    }
  };

  return (
    <div className="space-y-6">
      <div className="flex items-center justify-between">
        <h2 className="text-2xl font-bold">Financial Reports</h2>
        <div className="w-64">
          <Label className="text-sm text-gray-600">Academic Year</Label>
          <Select value={selectedYear} onValueChange={setSelectedYear}>
            <SelectTrigger>
              <SelectValue placeholder="Select year" />
            </SelectTrigger>
            <SelectContent>
              {academicYears.map((y) => (
                <SelectItem key={y.id} value={String(y.id)}>
                  {y.year_name} {y.is_current ? '(Current)' : ''}
                </SelectItem>
              ))}
            </SelectContent>
          </Select>
        </div>
      </div>

      {/* Financial Overview */}
      {financialOverview && (
        <div className="grid grid-cols-1 md:grid-cols-4 gap-4">
          <Card>
            <CardContent className="p-6">
              <p className="text-sm text-gray-600">Expected Revenue</p>
              <p className="text-2xl font-bold text-blue-600">
                SLE {parseFloat(financialOverview.expected_revenue).toLocaleString()}
              </p>
            </CardContent>
          </Card>
          <Card>
            <CardContent className="p-6">
              <p className="text-sm text-gray-600">Collected Revenue</p>
              <p className="text-2xl font-bold text-green-600">
                SLE {parseFloat(financialOverview.collected_revenue).toLocaleString()}
              </p>
            </CardContent>
          </Card>
          <Card>
            <CardContent className="p-6">
              <p className="text-sm text-gray-600">Outstanding Balance</p>
              <p className="text-2xl font-bold text-red-600">
                SLE {parseFloat(financialOverview.outstanding_balance).toLocaleString()}
              </p>
            </CardContent>
          </Card>
          <Card>
            <CardContent className="p-6">
              <p className="text-sm text-gray-600">Collection Rate</p>
              <p className="text-2xl font-bold text-purple-600">
                {financialOverview.collection_rate}%
              </p>
            </CardContent>
          </Card>
        </div>
      )}

      {/* Fee Collection by Class */}
      <Card>
        <CardHeader>
          <CardTitle>Fee Collection by Class</CardTitle>
        </CardHeader>
        <CardContent>
          <div className="overflow-x-auto">
            <table className="w-full">
              <thead>
                <tr className="border-b">
                  <th className="text-left py-2">Class</th>
                  <th className="text-right py-2">Students</th>
                  <th className="text-right py-2">Billed</th>
                  <th className="text-right py-2">Collected</th>
                  <th className="text-right py-2">Outstanding</th>
                  <th className="text-right py-2">Collection %</th>
                </tr>
              </thead>
              <tbody>
                {loading ? (
                  <tr>
                    <td colSpan="6" className="text-center py-4 text-gray-500">Loading...</td>
                  </tr>
                ) : feeCollection.length === 0 ? (
                  <tr>
                    <td colSpan="6" className="text-center py-4 text-gray-500">No data available</td>
                  </tr>
                ) : (
                  feeCollection.map((cls) => (
                    <tr key={cls.class_id} className="border-b hover:bg-gray-50">
                      <td className="py-3 font-medium">{cls.class_name}</td>
                      <td className="text-right py-3">{cls.total_students}</td>
                      <td className="text-right py-3">SLE {parseFloat(cls.total_billed || 0).toLocaleString()}</td>
                      <td className="text-right py-3 text-green-600">
                        SLE {parseFloat(cls.total_collected || 0).toLocaleString()}
                      </td>
                      <td className="text-right py-3 text-red-600">
                        SLE {parseFloat(cls.total_outstanding || 0).toLocaleString()}
                      </td>
                      <td className="text-right py-3 font-semibold">
                        {cls.collection_rate?.toFixed(1)}%
                      </td>
                    </tr>
                  ))
                )}
              </tbody>
            </table>
          </div>
        </CardContent>
      </Card>
    </div>
  );
};

export default FinancialReports;
