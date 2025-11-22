import { useCallback, useEffect, useMemo, useState } from 'react';
import axios from '../redux/axiosConfig';
import { useSelector } from 'react-redux';

export default function useTeacherTodaySchedule() {
  const { currentUser } = useSelector((s) => s.user);
  const [entries, setEntries] = useState([]);
  const [loading, setLoading] = useState(false);
  const [error, setError] = useState(null);

  const refresh = useCallback(async () => {
    if (!currentUser?.token) return;
    setLoading(true);
    setError(null);
    try {
      const teacherId = currentUser?.teacher?.id || currentUser?.id;
      const res = await axios.get(`/timetable/teacher/${teacherId}`, {
        headers: { Authorization: `Bearer ${currentUser?.token}` },
      });
      if (res.data?.success) {
        const all = res.data.timetable || [];
        const today = new Date().toLocaleDateString('en-US', { weekday: 'long' });
        const todays = all.filter(e => e.day_of_week === today);
        todays.sort((a,b) => (a.start_time||'').localeCompare(b.start_time||''));
        setEntries(todays);
      }
    } catch (e) {
      setError(e);
    } finally {
      setLoading(false);
    }
  }, [currentUser?.token, currentUser?.id, currentUser?.teacher?.id]);

  useEffect(() => { refresh(); }, [refresh]);

  const today = useMemo(() => new Date(), []);
  return { entries, loading, error, refresh };
}
