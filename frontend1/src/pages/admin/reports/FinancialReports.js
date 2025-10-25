import React, { useState, useEffect } from 'react';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import axios from 'axios';
import { toast } from 'sonner';

const FinancialReports = () => {
  const [financialOverview, setFinancialOverview] = useState(null);
  const [feeCollection, setFeeCollection] = useState([]);
  const [loading, setLoading] = useState(true);

  useEffect(() => {
    fetchFinancialData();
  }, []);

  const fetchFinancialData = async () => {
    try {
      const token = localStorage.getItem('token');
      const currentAcademicYear = JSON.parse(localStorage.getItem('currentAcademicYear'));

      const params = { academic_year_id: currentAcademicYear?.id };
      const headers = { Authorization: `Bearer ${token}` };

      const [overviewRes, collectionRes] = await Promise.all([
        axios.get(`${import.meta.env.VITE_API_BASE_URL}/reports/financial-overview`, { params, headers }),
        axios.get(`${import.meta.env.VITE_API_BASE_URL}/reports/fee-collection`, { params, headers })
      ]);

      if (overviewRes.data.success) setFinancialOverview(overviewRes.data.overview);
      if (collectionRes.data.success) setFeeCollection(collectionRes.data.collection);

      setLoading(false);
    } catch (error) {
      console.error('Error fetching financial data:', error);
      toast.error('Failed to load financial data');
      setLoading(false);
    }
  };

  return (
    <div className="space-y-6">
      <h2 className="text-2xl font-bold">Financial Reports</h2>

      {/* Financial Overview */}
      {financialOverview && (
        <div className="grid grid-cols-1 md:grid-cols-4 gap-4">
          <Card>
            <CardContent className="p-6">
              <p className="text-sm text-gray-600">Expected Revenue</p>
              <p className="text-2xl font-bold text-blue-600">
                ${parseFloat(financialOverview.expected_revenue).toLocaleString()}
              </p>
            </CardContent>
          </Card>
          <Card>
            <CardContent className="p-6">
              <p className="text-sm text-gray-600">Collected Revenue</p>
              <p className="text-2xl font-bold text-green-600">
                ${parseFloat(financialOverview.collected_revenue).toLocaleString()}
              </p>
            </CardContent>
          </Card>
          <Card>
            <CardContent className="p-6">
              <p className="text-sm text-gray-600">Outstanding Balance</p>
              <p className="text-2xl font-bold text-red-600">
                ${parseFloat(financialOverview.outstanding_balance).toLocaleString()}
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
                      <td className="text-right py-3">${parseFloat(cls.total_billed || 0).toLocaleString()}</td>
                      <td className="text-right py-3 text-green-600">
                        ${parseFloat(cls.total_collected || 0).toLocaleString()}
                      </td>
                      <td className="text-right py-3 text-red-600">
                        ${parseFloat(cls.total_outstanding || 0).toLocaleString()}
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
