import { useEffect, useState, useCallback } from 'react';
import axios from '../redux/axiosConfig';
import { useSelector } from 'react-redux';

export default function useInSession({ classId, studentId, subjectId, teacherId, date, time }) {
  const { currentUser } = useSelector((state) => state.user);
  const [inSession, setInSession] = useState(false);
  const [entries, setEntries] = useState([]);
  const [loading, setLoading] = useState(false);
  const [error, setError] = useState(null);
  const [lastCheckedAt, setLastCheckedAt] = useState(null);
  const refresh = useCallback(async () => {
    if (!classId && !studentId) return;
    setLoading(true);
    setError(null);
    try {
      const params = {};
      if (classId) params.class_id = classId;
      if (studentId) params.student_id = studentId;
      if (subjectId) params.subject_id = subjectId;
      if (teacherId) params.teacher_id = teacherId;
      if (date) params.date = date;
      if (time) params.time = time;
      const res = await axios.get(`/timetable/in-session`, {
        params,
        headers: { Authorization: `Bearer ${currentUser?.token}` },
      });
      setInSession(!!res.data?.in_session);
      setEntries(res.data?.entries || []);
      setLastCheckedAt(new Date());
    } catch (e) {
      setError(e);
    } finally {
      setLoading(false);
    }
  }, [classId, studentId, subjectId, teacherId, date, time, currentUser?.token]);

  useEffect(() => {
    if (!classId && !studentId) return;
    let cancelled = false;
    (async () => { if (!cancelled) await refresh(); })();
    return () => { cancelled = true; };
  }, [classId, studentId, subjectId, teacherId, date, time, currentUser?.token, refresh]);

  return { inSession, entries, loading, error, lastCheckedAt, refresh };
}
