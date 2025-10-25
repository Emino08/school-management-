import { Card, CardContent } from "@/components/ui/card";
import SeeNotice from "../../components/SeeNotice";
import CountUp from "react-countup";
import Students from "../../assets/img1.png";
import Lessons from "../../assets/subjects.svg";
import Tests from "../../assets/assignment.svg";
import Time from "../../assets/time.svg";
import {
  getClassStudents,
  getSubjectDetails,
} from "../../redux/sclassRelated/sclassHandle";
import { useDispatch, useSelector } from "react-redux";
import { useEffect, useState } from "react";
import axios from "axios";

const TeacherHomePage = () => {
  const dispatch = useDispatch();

  const { currentUser } = useSelector((state) => state.user);
  const { subjectDetails, sclassStudents } = useSelector(
    (state) => state.sclass,
  );

  const [dashboardStats, setDashboardStats] = useState(null);
  const API_URL = import.meta.env.VITE_API_BASE_URL || "http://localhost:8080/api";

  const classID = currentUser.teachSclass?._id;
  const subjectID = currentUser.teachSubject?._id;

  useEffect(() => {
    if (subjectID) {
      dispatch(getSubjectDetails(subjectID, "Subject"));
    }
    if (classID) {
      dispatch(getClassStudents(classID));
    }

    // Fetch dashboard statistics from backend
    const fetchDashboardStats = async () => {
      try {
        const response = await axios.get(`${API_URL}/teachers/dashboard-stats`, {
          headers: {
            Authorization: `Bearer ${currentUser?.token}`
          }
        });

        if (response.data?.success) {
          setDashboardStats(response.data.stats);
        }
      } catch (error) {
        console.error("Failed to fetch dashboard stats:", error);
      }
    };

    if (currentUser?.token) {
      fetchDashboardStats();
    }
  }, [dispatch, subjectID, classID, currentUser?.token]);

  console.log(subjectDetails, "subjectDetails");
  console.log(sclassStudents, "sclassStudents");
  console.log(dashboardStats, "dashboardStats");

  const numberOfStudents = dashboardStats?.students ?? (sclassStudents && sclassStudents.length) ?? 0;
  const numberOfSessions = dashboardStats?.lessons ?? (subjectDetails && subjectDetails.sessions) ?? 0;
  const numberOfTests = dashboardStats?.tests ?? 0;
  const totalHours = dashboardStats?.hours ?? 0;

  return (
    <div className="container max-w-7xl mx-auto mt-8 mb-8 px-4">
      <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
        <Card className="flex flex-col items-center justify-between h-[200px] p-4 text-center">
          <img src={Students} alt="Students" className="w-16 h-16 object-contain" />
          <p className="text-xl font-medium">Class Students</p>
          <span className="text-2xl md:text-3xl text-green-600 font-bold">
            <CountUp end={Number(numberOfStudents ?? 0)} duration={1.2} separator="," preserveValue redraw>
              {({ countUpRef }) => (<span ref={countUpRef} />)}
            </CountUp>
          </span>
        </Card>

        <Card className="flex flex-col items-center justify-between h-[200px] p-4 text-center">
          <img src={Lessons} alt="Lessons" className="w-16 h-16 object-contain" />
          <p className="text-xl font-medium">Total Lessons</p>
          <span className="text-2xl md:text-3xl text-green-600 font-bold">
            <CountUp end={Number(numberOfSessions ?? 0)} duration={1.2} separator="," preserveValue redraw>
              {({ countUpRef }) => (<span ref={countUpRef} />)}
            </CountUp>
          </span>
        </Card>

        <Card className="flex flex-col items-center justify-between h-[200px] p-4 text-center">
          <img src={Tests} alt="Tests" className="w-16 h-16 object-contain" />
          <p className="text-xl font-medium">Tests Taken</p>
          <span className="text-2xl md:text-3xl text-green-600 font-bold">
            <CountUp end={Number(numberOfTests ?? 0)} duration={1.2} separator="," preserveValue redraw>
              {({ countUpRef }) => (<span ref={countUpRef} />)}
            </CountUp>
          </span>
        </Card>

        <Card className="flex flex-col items-center justify-between h-[200px] p-4 text-center">
          <img src={Time} alt="Time" className="w-16 h-16 object-contain" />
          <p className="text-xl font-medium">Total Hours</p>
          <span className="text-2xl md:text-3xl text-green-600 font-bold">
            <CountUp end={Number(totalHours ?? 0)} duration={1.2} separator="," preserveValue redraw suffix="hrs">
              {({ countUpRef }) => (<span ref={countUpRef} />)}
            </CountUp>
          </span>
        </Card>

        <div className="col-span-1 md:col-span-2 lg:col-span-4">
          <Card>
            <CardContent className="p-4 flex flex-col">
              <SeeNotice />
            </CardContent>
          </Card>
        </div>
      </div>
    </div>
  );
};

export default TeacherHomePage;
