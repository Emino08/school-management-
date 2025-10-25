import {Button} from "flowbite-react";
import { useDispatch, useSelector } from "react-redux";
import {getSubjectList} from "../../redux/sclassRelated/sclassHandle";
import {useNavigate, useParams} from "react-router-dom";
import {useEffect} from "react";
import {getUserDetails} from "../../redux/userRelated/userHandle";
import {getClassSubjects} from "../../redux/sclassRelated/sclassHandle";

export default function TeacherClasses() {
    const navigate = useNavigate();
    const params = useParams();
    const dispatch = useDispatch();
    // const { subjectsList, response, loading, error } = useSelector(
    //     (state) => state.sclass,
    // );

    const { currentUser, response, loading, error } = useSelector(
        (state) => state.user);

    const address = "Teacher";
    const teacherID = currentUser._id;
    const teachSubject = currentUser.teachSubject?.subName;
    const teachSubjectID = currentUser.teachSubject?._id;

    useEffect(() => {
        dispatch(getUserDetails(teacherID, address));
        dispatch(getClassSubjects("651371c2f732e6ce55c1062c" ,teacherID));
    }, [dispatch, teacherID]);

    if (response) {
        console.log(response);
    } else if (error) {
        console.log(error);
    }


    return (
        <div>
            <Button outline gradientDuoTone="cyanToBlue">
                Cyan to Blue
            </Button>
        </div>
    )
}