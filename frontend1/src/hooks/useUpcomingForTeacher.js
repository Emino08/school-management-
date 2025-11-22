import { useCallback, useEffect, useState } from 'react';
import axios from '../redux/axiosConfig';
import { useSelector } from 'react-redux';

export default function useUpcomingForTeacher(limit = 1) {
  const { currentUser } = useSelector((s) => s.user);
  const [upcoming, setUpcoming] = useState([]);
  const [loading, setLoading] = useState(false);
  const [error, setError] = useState(null);

  const refresh = useCallback(async () => {
    if (!currentUser?.token) return;
    setLoading(true);
    setError(null);
    try {
      const res = await axios.get(`/timetable/upcoming`, {
        params: { limit },
        headers: { Authorization: `Bearer ${currentUser?.token}` },
      });
      if (res.data?.success) setUpcoming(res.data.upcoming_classes || []);
    } catch (e) {
      setError(e);
    } finally {
      setLoading(false);
    }
  }, [limit, currentUser?.token]);

  useEffect(() => {
    refresh();
  }, [refresh]);

  return { upcoming, next: upcoming[0], loading, error, refresh };
}
