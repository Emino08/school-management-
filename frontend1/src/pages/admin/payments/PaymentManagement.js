import React, { useState, useEffect } from 'react';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Tabs, TabsContent, TabsList, TabsTrigger } from '@/components/ui/tabs';
import { Badge } from '@/components/ui/badge';
import { FiDollarSign, FiFileText, FiCreditCard, FiPlus, FiDownload } from 'react-icons/fi';
import FeeStructures from './FeeStructures';
import RecordPayment from './RecordPayment';
import PaymentHistory from './PaymentHistory';
import InvoiceManagement from './InvoiceManagement';
import axios from '@/redux/axiosConfig';
import { toast } from 'sonner';

const PaymentManagement = () => {
  const [stats, setStats] = useState({
    totalCollected: 0,
    totalOutstanding: 0,
    recentPayments: 0,
    invoicesCount: 0
  });
  const [loading, setLoading] = useState(true);

  useEffect(() => {
    fetchStats();
  }, []);

  const fetchStats = async () => {
    try {
      const token = localStorage.getItem('token');
      const currentAcademicYear = JSON.parse(localStorage.getItem('currentAcademicYear'));

      if (!currentAcademicYear) {
        toast.error('Please select an academic year first');
        return;
      }

      // Fetch financial overview
      const response = await axios.get(
        `${import.meta.env.VITE_API_BASE_URL}/reports/financial-overview`,
        {
          params: { academic_year_id: currentAcademicYear.id }
        }
      );

      if (response.data.success) {
        const overview = response.data.overview || response.data.data || {};
        setStats({
          totalCollected: overview.collected_revenue || 0,
          totalOutstanding: overview.outstanding_balance || 0,
          recentPayments: overview.collected_revenue || 0,
          invoicesCount: overview.invoice_status?.reduce((acc, status) => acc + status.count, 0) || 0
        });
      }
      setLoading(false);
    } catch (error) {
      console.error('Error fetching stats:', error);
      toast.error('Failed to load payment statistics');
      setLoading(false);
    }
  };

  const StatCard = ({ title, value, icon: Icon, color, prefix = '' }) => (
    <Card>
      <CardContent className="p-6">
        <div className="flex items-center justify-between">
          <div>
            <p className="text-sm font-medium text-gray-600">{title}</p>
            <p className={`text-2xl font-bold ${color}`}>
              {prefix}{typeof value === 'number' ? value.toLocaleString() : value}
            </p>
          </div>
          <div className={`p-3 rounded-full ${color.replace('text', 'bg')}-100`}>
            <Icon className={`h-6 w-6 ${color}`} />
          </div>
        </div>
      </CardContent>
    </Card>
  );

  return (
    <div className="container mx-auto p-6 space-y-6">
      <div className="flex justify-between items-center">
        <div>
          <h1 className="text-3xl font-bold text-gray-900">Payment & Finance Management</h1>
          <p className="text-gray-600 mt-1">Manage fees, payments, and invoices</p>
        </div>
        <Button onClick={fetchStats} variant="outline">
          <FiDownload className="mr-2 h-4 w-4" />
          Refresh Stats
        </Button>
      </div>

      {/* Statistics Cards */}
      <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
        <StatCard
          title="Total Collected"
          value={stats.totalCollected}
          icon={FiDollarSign}
          color="text-green-600"
          prefix="$"
        />
        <StatCard
          title="Total Outstanding"
          value={stats.totalOutstanding}
          icon={FiCreditCard}
          color="text-red-600"
          prefix="$"
        />
        <StatCard
          title="Recent Payments (30d)"
          value={stats.recentPayments}
          icon={FiDollarSign}
          color="text-blue-600"
          prefix="$"
        />
        <StatCard
          title="Total Invoices"
          value={stats.invoicesCount}
          icon={FiFileText}
          color="text-purple-600"
        />
      </div>

      {/* Main Tabs */}
      <Tabs defaultValue="payments" className="w-full">
        <TabsList className="grid w-full grid-cols-4">
          <TabsTrigger value="payments">Payments</TabsTrigger>
          <TabsTrigger value="invoices">Invoices</TabsTrigger>
          <TabsTrigger value="fee-structures">Fee Structures</TabsTrigger>
          <TabsTrigger value="history">Payment History</TabsTrigger>
        </TabsList>

        <TabsContent value="payments" className="space-y-4">
          <Card>
            <CardHeader>
              <CardTitle>Record Payment</CardTitle>
              <CardDescription>Record a new payment from a student</CardDescription>
            </CardHeader>
            <CardContent>
              <RecordPayment onPaymentRecorded={fetchStats} />
            </CardContent>
          </Card>
        </TabsContent>

        <TabsContent value="invoices" className="space-y-4">
          <InvoiceManagement onInvoiceCreated={fetchStats} />
        </TabsContent>

        <TabsContent value="fee-structures" className="space-y-4">
          <FeeStructures />
        </TabsContent>

        <TabsContent value="history" className="space-y-4">
          <PaymentHistory />
        </TabsContent>
      </Tabs>
    </div>
  );
};

export default PaymentManagement;
