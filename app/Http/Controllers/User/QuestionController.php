<?php

namespace App\Http\Controllers\User;

use App\Models\Topic;
use App\Models\Answer;
use App\Models\Question;
use Illuminate\Support\Str;
use App\Models\ReportAnswer;
use Illuminate\Http\Request;
use App\Models\QuestionTopic;
use App\Models\ReportQuestion;
use App\Http\Controllers\Controller;

class QuestionController extends Controller
{
    //function edit title 
    public function edit_title($title){
       //remove space
       $removeSpace = str_replace(' ','-',$title);
       //remove special char
       $removeChar = preg_replace('/[^A-Za-z0-9\-]/', '',$removeSpace);
       //add space
       $addSpace = str_replace('-',' ',$removeChar);
       //make uppper case first letter title and add ? in last title
       return ucfirst($addSpace) . '?';
    }

    public function show(Question $question){

        $reported_question = false;
        $reported_answer = false;

        $answers = Answer::where('question_id',$question->id)->with('user')->latest()->get();
        $answered = Answer::where('question_id',$question->id)->where('user_id',auth()->id())->first();
        $report_question = ReportQuestion::where('question_id',$question->id)->where('user_id',auth()->id())->first();
        $topics = Topic::all();

        $report_question_types = [
            [
                'name' => 'Harrasment',
                'desc' => 'Disparaging or adversarial towards a person or group'
            ],  
            [
                'name' => 'Spam',
                'desc' => 'Undisclosed promotion for a link or product'
            ],
            [
                'name' => 'Insincere',
                'desc' => 'Not seeking genuine answers'
            ],
            [
                'name' => 'Poorly written',
                'desc' => 'Not in English or has very bad formatting, grammar, and spelling'
            ],
            [
                'name' => 'Incorrect topics',
                'desc' => 'Topics are irrelevant to the content or overly broad'
            ]
        ];

        $report_answer_types = $this->report_answer_types();

        if($report_question){
            $reported_question = true;
        }

        views($question)
        ->cooldown(86400)
        ->record();

        foreach($answers as $answer){
            $report_answer = ReportAnswer::where('answer_id',$answer->id)->where('user_id',auth()->id())->first();
            
            if($report_answer){
                $reported_answer = true;
            }

            views($answer)
            ->cooldown(86400)
            ->record();
        }

        $link = route('question.show',$question);
        $facebook = \Share::page($link)->facebook()->getRawLinks();
        $twitter = \Share::page($link)->twitter()->getRawLinks();

        return view('user.question.show',compact('question','answers','answered','topics','facebook','twitter','reported_question','report_question_types','reported_answer','report_answer_types'));
    }

    public function store(Request $request){
     
        $request->replace(['title' => $request->title . '?','topic_id' => $request->topic_id]);
        $user = auth()->user();

        $request->validate([
            'title' => 'required|min:10|unique:questions,title',
        ]);

        $title = $this->edit_title($request->title);
  
        $question = $user->questions()->create([
            'title' => $title,
            'title_slug' => Str::of($title)->slug('-'),
        ]);

        $title_slug = Str::of($title)->slug('-');

        if($request->topic_id){
            $check = 0;
            for ($i=0; $i < count($request->topic_id) ; $i++) {
                $check++;
                //check if added topics more than 8
                if($check > 8){
                    break;
                }
                QuestionTopic::create([
                    'question_id' => $question->id,
                    'topic_id' => $request->topic_id[$i]
                ]);
            }
        }

        return redirect()->route('question.show',$title_slug)->with('message',['text' => 'Question added successfully!', 'class' => 'success']);
    }

    public function update(Question $question,Request $request){

        if($request->title){

            $request->validate([
                'title' => 'required',
                'link' => 'url'
            ]);
    
            $user = auth()->user();
    
            $title = $this->edit_title($request->title);
            $title_slug = Str::of($title)->slug('-');

            $question->update([
                'title' => $title,
                'title_slug' => $title_slug,
            ]);
        }

        if($request->topic_id){

            $qtopics = QuestionTopic::where('question_id',$question->id)->get();

            foreach($qtopics as $qtopic){
                $qtopic->delete();
            }

            for ($i=0; $i < count($request->topic_id) ; $i++) { 
                QuestionTopic::create([
                    'question_id' => $question->id,
                    'topic_id' => $request->topic_id[$i]
                ]);
            }

            $title_slug = Str::of($question->title)->slug('-');
        }

        return redirect()->route('question.show',$title_slug)->with('message',['text' => 'Question updated successfully!', 'class' => 'success']);
    }

    public function report(Request $request,Question $question){

        $user_id = auth()->id();
        $report = ReportQuestion::where('user_id',$user_id)->where('question_id',$question->id)->first();

        if($report){
            return back()->with('message',['text' => 'Question already reported!', 'class' => 'danger']);
        }else{
            ReportQuestion::create([
                'user_id' => $user_id,
                'question_id' => $question->id,
                'type' => $request->type,
            ]);

            return back()->with('message',['text' => 'Question reported successfully!', 'class' => 'success']);
        }
    }

    public function destroy(Question $question){

        $qtopics = QuestionTopic::where('question_id',$question->id)->get();

        foreach($qtopics as $qtopic){
            $qtopic->delete();
        }

        foreach($question->answers as $answer){
            $answer->delete();
        }

        $question->delete();

        return redirect()->route('content.index')->with('message',['text' => 'Question deleted successfully!', 'class' => 'success']);
    }
}
