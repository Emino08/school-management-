import * as React from 'react';
import { useDispatch } from 'react-redux';
import { underControl } from '../redux/userRelated/userSlice';
import { underStudentControl } from '../redux/studentRelated/studentSlice';
import { useToast } from '@/hooks/use-toast';
import { useEffect } from 'react';

const Popup = ({ message, setShowPopup, showPopup }) => {
    const dispatch = useDispatch();
    const { toast } = useToast();

    useEffect(() => {
        if (showPopup && message) {
            toast({
                variant: message === "Done Successfully" ? "default" : "destructive",
                title: message === "Done Successfully" ? "Success" : "Error",
                description: message,
            });

            const timer = setTimeout(() => {
                setShowPopup(false);
                dispatch(underControl());
                dispatch(underStudentControl());
            }, 2000);

            return () => clearTimeout(timer);
        }
    }, [showPopup, message, toast, setShowPopup, dispatch]);

    return null;
};

export default Popup;
