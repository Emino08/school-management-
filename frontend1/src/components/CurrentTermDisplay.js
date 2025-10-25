import React, { useEffect, useState } from 'react';
import axios from 'axios';
import { Card, CardContent, CardHeader, CardTitle } from './ui/card';
import { Badge } from './ui/badge';
import { Calendar, TrendingUp, CheckCircle } from 'lucide-react';
import { Button } from './ui/button';
import { toast } from 'sonner';

const CurrentTermDisplay = ({ currentUser }) => {
  const [termInfo, setTermInfo] = useState(null);
  const [loading, setLoading] = useState(true);
  const API_URL = import.meta.env.VITE_API_BASE_URL || 'http://localhost:8080/api';

  useEffect(() => {
    fetchCurrentTerm();
  }, []);

  const fetchCurrentTerm = async () => {
    try {
      const res = await axios.get(`${API_URL}/terms/current`, {
        headers: { Authorization: `Bearer ${currentUser?.token}` }
      });
      if (res.data?.success) {
        setTermInfo(res.data);
      }
    } catch (error) {
      console.error('Failed to fetch current term:', error);
    } finally {
      setLoading(false);
    }
  };

  const handleToggleTerm = async (termNumber) => {
    if (!termInfo?.academic_year?.id) return;

    try {
      const res = await axios.post(
        `${API_URL}/terms/toggle/${termInfo.academic_year.id}`,
        { term_number: termNumber },
        { headers: { Authorization: `Bearer ${currentUser?.token}` } }
      );

      if (res.data?.success) {
        toast.success(res.data.message);
        fetchCurrentTerm();
      }
    } catch (error) {
      toast.error('Failed to toggle term: ' + (error.response?.data?.message || error.message));
    }
  };

  if (loading) {
    return (
      <Card>
        <CardContent className="p-6">
          <p className="text-sm text-gray-500">Loading term information...</p>
        </CardContent>
      </Card>
    );
  }

  if (!termInfo?.academic_year) {
    return (
      <Card>
        <CardContent className="p-6">
          <p className="text-sm text-gray-500">No active academic year found</p>
        </CardContent>
      </Card>
    );
  }

  const { academic_year, current_term } = termInfo;
  const progress = current_term
    ? (current_term.exams_published / current_term.exams_required) * 100
    : 0;

  return (
    <Card>
      <CardHeader>
        <CardTitle className="flex items-center justify-between">
          <span className="flex items-center gap-2">
            <Calendar className="w-5 h-5" />
            Current Term
          </span>
          <Badge variant="default" className="text-lg">
            Term {academic_year.current_term} of {academic_year.total_terms}
          </Badge>
        </CardTitle>
      </CardHeader>
      <CardContent className="space-y-4">
        <div className="space-y-2">
          <div className="flex justify-between text-sm">
            <span className="font-medium">Academic Year:</span>
            <span>{academic_year.year_name}</span>
          </div>

          {current_term && (
            <>
              <div className="flex justify-between text-sm">
                <span className="font-medium">Term Name:</span>
                <span>{current_term.term_name}</span>
              </div>

              <div className="flex justify-between text-sm">
                <span className="font-medium">Duration:</span>
                <span>
                  {new Date(current_term.start_date).toLocaleDateString()} -
                  {new Date(current_term.end_date).toLocaleDateString()}
                </span>
              </div>

              <div className="space-y-1">
                <div className="flex justify-between text-sm">
                  <span className="font-medium">Exams Progress:</span>
                  <span>
                    {current_term.exams_published} / {current_term.exams_required} published
                  </span>
                </div>
                <div className="w-full bg-gray-200 rounded-full h-2">
                  <div
                    className="bg-blue-600 h-2 rounded-full transition-all"
                    style={{ width: `${progress}%` }}
                  />
                </div>
                {progress >= 100 && academic_year.current_term < academic_year.total_terms && (
                  <p className="text-xs text-green-600 flex items-center gap-1">
                    <CheckCircle className="w-3 h-3" />
                    All exams published! System will auto-toggle to next term.
                  </p>
                )}
              </div>
            </>
          )}

          <div className="bg-blue-50 p-3 rounded-md text-sm">
            <strong>System:</strong> {academic_year.exams_per_term} exam(s) per term
          </div>
        </div>

        {/* Manual term toggle (admin only) */}
        <div className="space-y-2 pt-2 border-t">
          <p className="text-xs text-gray-600 font-medium">Manual Term Control:</p>
          <div className="flex gap-2">
            {[...Array(academic_year.total_terms)].map((_, i) => (
              <Button
                key={i + 1}
                size="sm"
                variant={academic_year.current_term === i + 1 ? 'default' : 'outline'}
                onClick={() => handleToggleTerm(i + 1)}
                disabled={academic_year.current_term === i + 1}
              >
                Term {i + 1}
              </Button>
            ))}
          </div>
          <p className="text-xs text-gray-500">
            Click to manually switch terms (use with caution)
          </p>
        </div>
      </CardContent>
    </Card>
  );
};

export default CurrentTermDisplay;
