import React from 'react';
import { useNavigate } from 'react-router-dom';
import { useDispatch, useSelector } from 'react-redux';
import { authLogout } from '../redux/userRelated/userSlice';
import { Card, CardContent } from "@/components/ui/card";
import { Button } from "@/components/ui/button";

const Logout = () => {
    const currentUser = useSelector(state => state.user.currentUser);

    const navigate = useNavigate();
    const dispatch = useDispatch();

    const handleLogout = () => {
        dispatch(authLogout());
        navigate('/');
    };

    const handleCancel = () => {
        navigate(-1);
    };

    return (
        <div className="flex items-center justify-center min-h-screen">
            <Card className="w-full max-w-md shadow-lg bg-purple-100/40">
                <CardContent className="flex flex-col items-center justify-center p-8 space-y-4">
                    <h1 className="text-2xl font-bold text-black">{currentUser.name}</h1>
                    <p className="text-base text-center text-black mb-4">
                        Are you sure you want to log out?
                    </p>
                    <Button
                        onClick={handleLogout}
                        className="w-full bg-red-600 hover:bg-gray-800 text-white"
                    >
                        Log Out
                    </Button>
                    <Button
                        onClick={handleCancel}
                        className="w-full bg-purple-700 hover:bg-gray-800 text-white"
                    >
                        Cancel
                    </Button>
                </CardContent>
            </Card>
        </div>
    );
};

export default Logout;
