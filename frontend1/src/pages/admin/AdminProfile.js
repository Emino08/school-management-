import { useSelector } from 'react-redux';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';

const AdminProfile = () => {
    const { currentUser } = useSelector((state) => state.user);

    return (
        <div className="container mx-auto p-6 max-w-2xl">
            <Card>
                <CardHeader>
                    <CardTitle className="text-2xl">Admin Profile</CardTitle>
                </CardHeader>
                <CardContent className="space-y-4">
                    <div className="space-y-2">
                        <p className="text-base">
                            <span className="font-semibold">Name:</span> {currentUser.name}
                        </p>
                        <p className="text-base">
                            <span className="font-semibold">Email:</span> {currentUser.email}
                        </p>
                        <p className="text-base">
                            <span className="font-semibold">School:</span> {currentUser.schoolName}
                        </p>
                    </div>
                </CardContent>
            </Card>
        </div>
    )
}

export default AdminProfile
