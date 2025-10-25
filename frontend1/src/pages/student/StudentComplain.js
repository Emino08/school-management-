import { useEffect, useState } from "react";
import { MdRotateRight } from "react-icons/md";
import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card";
import { Input } from "@/components/ui/input";
import { Label } from "@/components/ui/label";
import { Textarea } from "@/components/ui/textarea";
import { Button } from "@/components/ui/button";
import { toast } from 'sonner';
import { addStuff } from "../../redux/userRelated/userHandle";
import { useDispatch, useSelector } from "react-redux";

const StudentComplain = () => {
  const [complaint, setComplaint] = useState("");
  const [date, setDate] = useState("");

  const dispatch = useDispatch();

  const { status, currentUser, error } = useSelector((state) => state.user);

  const user = currentUser?._id;
  const school = currentUser?.school?._id;
  const address = "Complain";

  const [loader, setLoader] = useState(false);

  const fields = {
    user,
    date,
    complaint,
    school,
  };

  const submitHandler = (event) => {
    event.preventDefault();
    setLoader(true);
    dispatch(addStuff(fields, address));
  };

  useEffect(() => {
    if (status === "added") {
      setLoader(false);
      setMessage("Done Successfully");
    } else if (error) {
      setLoader(false);
      toast.error('Network Error', { description: 'Unable to connect to the server.' });
    }
  }, [status, error]);

  return (
    <>
      <div className="flex flex-1 items-center justify-center">
        <div className="w-full max-w-[550px] px-3 py-24">
          <Card>
            <CardHeader>
              <CardTitle className="text-2xl">Complaint</CardTitle>
            </CardHeader>
            <CardContent>
              <form onSubmit={submitHandler} className="space-y-6">
                <div className="space-y-2">
                  <Label htmlFor="date">Select Date</Label>
                  <Input
                    id="date"
                    type="date"
                    value={date}
                    onChange={(event) => setDate(event.target.value)}
                    required
                  />
                </div>
                <div className="space-y-2">
                  <Label htmlFor="complaint">Write your complaint</Label>
                  <Textarea
                    id="complaint"
                    placeholder="Enter your complaint..."
                    value={complaint}
                    onChange={(event) => {
                      setComplaint(event.target.value);
                    }}
                    required
                    rows={4}
                  />
                </div>
                <Button
                  type="submit"
                  disabled={loader}
                  className="w-full bg-blue-600 hover:bg-blue-700"
                >
                  {loader ? (
                    <MdRotateRight className="w-6 h-6 animate-spin" />
                  ) : (
                    "Add"
                  )}
                </Button>
              </form>
            </CardContent>
          </Card>
        </div>
      </div>
      <Popup
        message={message}
        setShowPopup={setShowPopup}
        showPopup={showPopup}
      />
    </>
  );
};

export default StudentComplain;
