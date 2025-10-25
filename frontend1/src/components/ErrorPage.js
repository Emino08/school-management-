import React from 'react';

const ErrorPage = () => {
    return (
        <div
            className="flex justify-center items-center h-screen text-white"
            style={{
                fontFamily: '"Josefin Sans", sans-serif',
                backgroundImage: 'url(https://images.pexels.com/photos/593158/pexels-photo-593158.jpeg?auto=compress&cs=tinysrgb&w=1260&h=750&dpr=1)',
                backgroundSize: 'cover',
                backgroundPosition: 'center'
            }}
        >
            <div className="max-w-3xl p-5 text-center">
                <h1 className="mb-10 text-3xl font-bold text-red-900">
                    Oops, something went wrong
                </h1>
                <p className="text-lg leading-relaxed">
                    We apologize for the inconvenience. Our website is currently experiencing technical difficulties. Please check back later.
                </p>
            </div>
        </div>
    );
};

export default ErrorPage;
