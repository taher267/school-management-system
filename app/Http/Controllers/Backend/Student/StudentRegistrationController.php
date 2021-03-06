<?php

namespace App\Http\Controllers\Backend\Student;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Brian2694\Toastr\Facades\Toastr;
use Illuminate\Support\Facades\Hash;
use App\Models\AssignStudent;
use App\Models\User;
use App\Models\DiscountStudent;

use App\Models\StudentClass;
use App\Models\StudentYear;
use App\Models\StudentGroup;
use App\Models\StudentShift;
use DB;
use PDF;

class StudentRegistrationController extends Controller
{
    public function ViewRegistration()
    {
      
         $this->data['classes']     = StudentClass::all();
        $this->data['years']        = StudentYear::all();	

    
        $this->data['class_id']     = StudentClass::orderBy('id','desc')->first()->id;
        $this->data['year_id']      = StudentYear::orderBy('id','desc')->first()->id;	

        $this->data['allData']      = AssignStudent::where('year_id', $this->data['year_id'])->where('class_id', $this->data['class_id'])->get();

        return view('backend.student.student_reg.student_view',$this->data);    
    }

//End method
    
    public function StudentClassYearWise(Request $request)
    {
       $this->data['classes']       = StudentClass::all();
        $this->data['years']        = StudentYear::all();	

    
        $this->data['class_id']     = $request->class_id;
        $this->data['year_id']      = $request->year_id;

        $this->data['allData']      = AssignStudent::where('year_id',$request->year_id)->where('class_id', $request->class_id)->get();

        return view('backend.student.student_reg.student_view',$this->data);   
    }


//End method



    public function AddStudentRegistration()
    {
      $this->data['classes'] = StudentClass::all();
      $this->data['years']   = StudentYear::all();	
      $this->data['groups'] = StudentGroup::all();	
      $this->data['shifts'] = StudentShift::all();	
      
      return view('backend.student.student_reg.student_add',$this->data);
    }

//End method

    
    public function StudentRegistraionStore(Request $request)

    {
	 DB::transaction(function()use($request){
         
	$checkYear = StudentYear::find($request->year_id)->name;
    $student =User::where('usertype','Student')->orderBy('id','DESC')->first();

	if ($student == null) {
		 $firstReg = 0;
		 $studentId = $firstReg+1;
		 if ($studentId < 10) {
		 	$id_no = '000'.$studentId;
		 }elseif ($studentId < 100) {
			 $id_no = '00'.$studentId;
		 }elseif ($studentId < 1000) {
		      $id_no = '0'.$studentId;
		 }


	} else{
    $student =User::where('usertype','Student')->orderBy('id','DESC')->first()->id;
     $studentId = $student+1;
	     if ($studentId < 10) {
		 	$id_no = '000'.$studentId;
		 }elseif ($studentId < 100) {
			 $id_no = '00'.$studentId;
		 }elseif ($studentId < 1000) {
		      $id_no = '0'.$studentId;
		 }

     }// End else conditions


 	$final_id_no        = $checkYear.$id_no;

 	$user               = new User();
 	$code               = rand(0000,99999);
 	$user->id_no        = $final_id_no;
 	$user->password     = bcrypt($code);
 	$user->usertype     = 'Student';
 	$user->code         =  $code;

 	$user->name          = $request->name;
 	$user->fname         = $request->fname;
 	$user->mname         = $request->mname;
 	$user->mobile        = $request->mobile;
 	$user->address       = $request->address;
 	$user->gender        = $request->gender;
 	$user->religion      = $request->religion;
 	$user->dob           = date('Y-m-d',strtotime($request->dob));

 	 if ($request->file('image')) {
		$file = $request->file('image');
		// @unlink(public_path('uploads/user_image/'.$user->image));
		$filename = date('YmdHi').$file->getClientOriginalName();
		$file->move(public_path('uploads/student_image'),$filename);
		$user['image'] = $filename;
	}

    $user->save();
    

    $assignstudent               = new AssignStudent();
    $assignstudent->student_id   = $user->id;
    $assignstudent->year_id      = $request->year_id;
    $assignstudent->class_id     = $request->class_id;
    $assignstudent->group_id     = $request->group_id;
    $assignstudent->shift_id     = $request->shift_id;
    
    $assignstudent->save();


    $discount_student                       = new DiscountStudent();
    $discount_student->assign_student_id	= $assignstudent->id;
    $discount_student->fee_category_id      = '1';
    $discount_student->discount             = $request->discount;

     $discount_student->save();

     }); //End DB transaction


      Toastr::success('Student Registration Successfully Saved :)' ,'Success');
       return redirect()->route('reg.view');

    }

   //End method
   

   public function EditStudentRegistration($student_id)
   {
   	  
     $this->data['classes']     = StudentClass::all();
      $this->data['years']      = StudentYear::all();	
      $this->data['groups']     = StudentGroup::all();	
      $this->data['shifts']     = StudentShift::all();	

      $this->data['editData']  = AssignStudent::with(['student','discount'])->where('student_id',$student_id)->first();
      
     // dd($this->data['editData']->toArray());
      return view('backend.student.student_reg.edit_student',$this->data);


   }

  //End method


	  public function UpdateStudentRegistration(Request $request,$student_id)
	  { DB::transaction(function()use($request,$student_id){
         


    $user                =  User::where('id',$student_id)->first();
 	$user->name          = $request->name;
 	$user->fname         = $request->fname;
 	$user->mname         = $request->mname;
 	$user->mobile        = $request->mobile;
 	$user->address       = $request->address;
 	$user->gender        = $request->gender;
 	$user->religion      = $request->religion;
 	$user->dob           = date('Y-m-d',strtotime($request->dob));

 	 if ($request->file('image')) {
		$file = $request->file('image');
		@unlink(public_path('uploads/student_image/'.$user->image));
		$filename = date('YmdHi').$file->getClientOriginalName();
		$file->move(public_path('uploads/student_image'),$filename);
		$user['image'] = $filename;
	}

    $user->save();
    

    $assignstudent  = AssignStudent::where('id',$request->id)->where('student_id',$student_id)->first();

    $assignstudent->year_id      = $request->year_id;
    $assignstudent->class_id     = $request->class_id;
    $assignstudent->group_id     = $request->group_id;
    $assignstudent->shift_id     = $request->shift_id;
    
    $assignstudent->save();


$discount_student = DiscountStudent::where('assign_student_id',$request->id)->first();
 $discount_student->discount  = $request->discount;

     $discount_student->save();

     }); //End DB transaction


      Toastr::success('Student Registration Successfully Update :)' ,'Success');
       return redirect()->route('reg.view');

 }


  //End method
  //End method


 	public function StudentPromotion($student_id)
 	{
 	 
 	  $this->data['classes']     = StudentClass::all();
      $this->data['years']      = StudentYear::all();	
      $this->data['groups']     = StudentGroup::all();	
      $this->data['shifts']     = StudentShift::all();	

      $this->data['editData']  = AssignStudent::with(['student','discount'])->where('student_id',$student_id)->first();
      
     // dd($this->data['editData']->toArray());
      return view('backend.student.student_reg.student_promotion',$this->data);



 	}

  //End method
  //End method

  public function StudentRegistrationPromotionController(Request $request,$student_id)
   {
   	
   	DB::transaction(function()use($request,$student_id){
         


    $user                =  User::where('id',$student_id)->first();
 	$user->name          = $request->name;
 	$user->fname         = $request->fname;
 	$user->mname         = $request->mname;
 	$user->mobile        = $request->mobile;
 	$user->address       = $request->address;
 	$user->gender        = $request->gender;
 	$user->religion      = $request->religion;
 	$user->dob           = date('Y-m-d',strtotime($request->dob));

 	 if ($request->file('image')) {
		$file = $request->file('image');
		@unlink(public_path('uploads/student_image/'.$user->image));
		$filename = date('YmdHi').$file->getClientOriginalName();
		$file->move(public_path('uploads/student_image'),$filename);
		$user['image'] = $filename;
	}

    $user->save();
    

    $assignstudent               = new AssignStudent();
    $assignstudent->student_id   = $user->id;
    $assignstudent->year_id      = $request->year_id;
    $assignstudent->class_id     = $request->class_id;
    $assignstudent->group_id     = $request->group_id;
    $assignstudent->shift_id     = $request->shift_id;
    
    $assignstudent->save();


    $discount_student                          = new DiscountStudent();
    $discount_student->assign_student_id	   = $assignstudent->id;
    $discount_student->fee_category_id         = '1';
    $discount_student->discount                = $request->discount;

     $discount_student->save();

     }); //End DB transaction


      Toastr::success('Student Promotion Successfully Update :)' ,'Success');
       return redirect()->route('reg.view');

   }

 //End method
  //End method


   public function StudentDetails($student_id)
   {
   	  $this->data['details']  = AssignStudent::with(['student','discount'])->where('student_id',$student_id)->first();
      
     // dd($this->data['editData']->toArray());
   	$pdf = PDF::loadView('backend.student.student_reg.student_pdf',$this->data);
	$pdf->SetProtection(['copy', 'print'], '', 'pass');
	return $pdf->stream('document.pdf');

     
   }



}
