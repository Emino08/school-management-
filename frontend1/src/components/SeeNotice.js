import React, { useEffect, useMemo, useRef } from 'react';
import { useDispatch, useSelector } from 'react-redux';
import { getAllNotices } from '../redux/noticeRelated/noticeHandle';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import TableViewTemplate from './TableViewTemplate';

const SeeNotice = React.memo(() => {
    const dispatch = useDispatch();
    const hasFetched = useRef(false);

    const { currentUser, currentRole } = useSelector(state => state.user);
    const { noticesList, loading, error, response } = useSelector((state) => state.notice);

    // Memoize admin ID
    const adminId = useMemo(() => 
        currentUser?.admin?.id || currentUser?.id || currentUser?.school?._id,
        [currentUser]
    );

    useEffect(() => {
        if (hasFetched.current || !adminId) return;
        
        hasFetched.current = true;
        
        if (currentRole === "Admin" || currentRole === "Principal") {
            dispatch(getAllNotices(adminId, "Notice"));
        } else if (currentUser?.school?._id) {
            dispatch(getAllNotices(currentUser.school._id, "Notice"));
        }
    }, [adminId, currentRole, dispatch, currentUser]);

    // Memoize columns
    const noticeColumns = useMemo(() => [
        { id: 'title', label: 'Title', minWidth: 170 },
        { id: 'details', label: 'Details', minWidth: 100 },
        { id: 'date', label: 'Date', minWidth: 170 },
    ], []);

    // Memoize rows transformation
    const noticeRows = useMemo(() => {
        if (!noticesList || !Array.isArray(noticesList)) return [];
        
        return noticesList.map((notice) => {
            const date = new Date(notice.date);
            const dateString = date.toString() !== "Invalid Date" 
                ? date.toISOString().substring(0, 10) 
                : "Invalid Date";
            return {
                title: notice.title,
                details: notice.details,
                date: dateString,
                id: notice._id,
            };
        });
    }, [noticesList]);

    return (
        <div className="mt-12 mr-5">
            {loading ? (
                <div className="text-xl">Loading...</div>
            ) : error ? (
                <div className="text-xl text-red-500">Unable to load notices. Please try again later.</div>
            ) : response ? (
                <div className="text-xl">No Notices to Show Right Now</div>
            ) : (
                <Card>
                    <CardHeader>
                        <CardTitle className="text-3xl">Notices</CardTitle>
                    </CardHeader>
                    <CardContent>
                        {noticeRows.length > 0 ? (
                            <TableViewTemplate columns={noticeColumns} rows={noticeRows} />
                        ) : (
                            <div className="text-gray-500">No notices available</div>
                        )}
                    </CardContent>
                </Card>
            )}
        </div>
    );
});

SeeNotice.displayName = 'SeeNotice';

export default SeeNotice;
