import React, { useEffect } from 'react';
import { useDispatch, useSelector } from 'react-redux';
import { getAllNotices } from '../redux/noticeRelated/noticeHandle';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import TableViewTemplate from './TableViewTemplate';

const SeeNotice = () => {
    const dispatch = useDispatch();

    const { currentUser, currentRole } = useSelector(state => state.user);
    const { noticesList, loading, error, response } = useSelector((state) => state.notice);

    useEffect(() => {
        if (currentUser && currentRole === "Admin") {
            dispatch(getAllNotices(currentUser._id, "Notice"));
        }
        else if (currentUser?.school?._id) {
            dispatch(getAllNotices(currentUser.school._id, "Notice"));
        }
    }, [currentUser, currentRole, dispatch]);

    if (error) {
        console.log(error);
    }

    const noticeColumns = [
        { id: 'title', label: 'Title', minWidth: 170 },
        { id: 'details', label: 'Details', minWidth: 100 },
        { id: 'date', label: 'Date', minWidth: 170 },
    ];

    const noticeRows = noticesList.map((notice) => {
        const date = new Date(notice.date);
        const dateString = date.toString() !== "Invalid Date" ? date.toISOString().substring(0, 10) : "Invalid Date";
        return {
            title: notice.title,
            details: notice.details,
            date: dateString,
            id: notice._id,
        };
    });

    return (
        <div className="mt-12 mr-5">
            {loading ? (
                <div className="text-xl">Loading...</div>
            ) : response ? (
                <div className="text-xl">No Notices to Show Right Now</div>
            ) : (
                <Card>
                    <CardHeader>
                        <CardTitle className="text-3xl">Notices</CardTitle>
                    </CardHeader>
                    <CardContent>
                        {Array.isArray(noticesList) && noticesList.length > 0 &&
                            <TableViewTemplate columns={noticeColumns} rows={noticeRows} />
                        }
                    </CardContent>
                </Card>
            )}
        </div>
    );
}

export default SeeNotice;