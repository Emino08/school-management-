import React, { useEffect } from "react";

const Modal = ({ message, isSuccess, onClose }) => {
  useEffect(() => {
    const timeout = setTimeout(() => {
      onClose();
    }, 2000);

    return () => clearTimeout(timeout);
  }, [onClose]);

  return (
    <div className="fixed top-0 left-0 w-full h-full flex items-center justify-center bg-black bg-opacity-50 z-50">
      <div className="bg-white p-4 rounded-lg shadow-md max-w-md text-center">
        <p className={isSuccess ? "text-green-600" : "text-red-600"}>
          {message}
        </p>
      </div>
    </div>
  );
};

export default Modal;
