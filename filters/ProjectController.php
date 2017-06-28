<?php

namespace App\Http\Controllers;
use App\Client;
use App\Project;
use App\Application;
use App\PaaS;
use App\SaaS;
use App\Ahp;
use App\Jobs\GeneratePdf;
use App\Criterion;
use App\Alternative;
use Carbon\Carbon;
use Illuminate\Http\Request;
use \PDF;
use Illuminate\Support\Facades\Input;
use Illuminate\Support\Facades\DB;
use Config;
use Illuminate\Support\Facades\Storage;
use App\Events\ApplicationReadyAfterAnalysis;
use App\Listeners\ApplicationAssociated;
use App\Events\ApplicationAnalyse;
use \Zipper;
use Illuminate\Support\Collection;
use App\IaaS;
use Gate;
use App\User;


class ProjectController extends Controller
{
	 public function analyze(Project $project,Application $application)
    {
            $found=0;
            $disposition="";
            $cloud="";
            $completed= DB::table('application_project')->select('completed','application_id','disposition_1','cloud_suitability_1')->where('project_id','=',$project->id)->where('application_id','=',$application->id)->get();
            foreach($completed as $comp)
            {
                $disposition=$comp->disposition_1;
                $cloud=$comp->cloud_suitability_1;
                if($comp->completed==2 or $comp->completed==4)
                {
                    $found=1;
                }
                elseif($comp->completed==0)
                {
                    $found=0;
                }
                else
                {
                    $found=2;
                }
            }
            $data = ([
            'found'=> $found,
            'disposition'=> $disposition,
            'cloud'=>$cloud
            ]);
            return \Response::json($data); 
    }

    public function index(Client $client)
    {   
        if(Gate::denies('access_client',$client->id))
        {
            return view('errors.403');
        }
        $projects = Project::where('client_id','=',$client->id)->paginate(50);
        $application_project = DB::table('application_project')
        ->join('projects','application_project.project_id','=','projects.id')
        ->select('application_project.project_id','application_project.application_id','application_project.completed')
        ->where('projects.client_id','=',$client->id)
        /*->groupby('categories.name','applications.name')*/
        ->get();
        return view('projects.index',compact('client','projects','application_project'));
    }

    public function pdfzip(Client $client,Project $project)
    {
        $timestamp= date('Y-m-d H-i-s');
       
        $files = glob(\Storage::disk('nfs')->url('pdf/'.$project->client_id.'/'.$project->id.'/*.pdf'));
        Zipper::make(\Storage::disk('nfs')->url('zip/'.$client->name.'-'.$project->name.'-'.$timestamp.'.zip'))->add($files)->close();

        $file = \Storage::disk('nfs')->url('zip/'.$client->name.'-'.$project->name.'-'.$timestamp.'.zip');

        if (file_exists($file)) 
        {
            header('Content-Description: File Transfer');
            header('Content-Type: application/octet-stream');
            header('Content-Disposition: attachment; filename='.basename($file));
            header('Content-Transfer-Encoding: binary');
            header('Expires: 0');
            header('Cache-Control: must-revalidate');
            header('Pragma: public');
            header('Content-Length: ' . filesize($file));
            ob_clean();
            flush();
            readfile($file);
            exit;
        }
        

    }


    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function edit(Client $client,Project $project)
    {
        if(Gate::denies('access_client',$client->id))
        {
            return view('errors.403');
        }
        $iaas=IaaS::all();
        return view('projects.edit',compact('project','client','iaas'));
    }


    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request,Project $project)
    {
        $this->validate($request,[
        'name' => 'required|regex:/^[0-9A-Za-z. \-]+$/|unique:projects,name,'.$project->id.',id,client_id,'.$project->client_id.'|max:'.config('global.maxlength'),
        'description' =>'nullable|max:'.config('global.description'),
        'cloud_preferences'=>'required',
        ]);

        $project->name=request('name');
        $project->description=request('description');
        $project->update();
        $client = Client::find($project->client_id);
        $client->touch();
        session()->flash('message','Project record updated');
        $project->iaas()->sync($request->input('cloud_preferences')?: []);
        return(redirect('/clients/'.$project->client_id.'/projects'));
    } 

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function delete(Project $project)
    {
        DB::table('application_criteria_matrix')->where('project_id', $project->id)->delete();
        DB::table('application_criteria_ranking')->where('project_id', $project->id)->delete();
        DB::table('application_alternative_ranking')->where('project_id', $project->id)->delete();
        $project->applications()->detach();
        $project->delete();
        $client = Client::find($project->client_id);
        $client->touch();
        session()->flash('message','Project deleted');
        return back(); 
    }


    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create(Client $client)
    {
         if(Gate::denies('access_client',$client->id))
        {
            return view('errors.403');
        }
         /*$criterias = Criterion::all();
         $alternatives = Alternative::all();
         $ahps = Ahp::where('client_id','=',$client->id)->orWhere('client_id','=',0)->get();*/
         $iaas=IaaS::all();
         
         return view('projects.add',compact('client','iaas'));
    }

    public function store_paas(Request $request,Client $client)
    {   
        // return $request->input('cloud_preferences');
        // return $request->name;
        $project_name = $request->name;
        $cloud_preferences = $request->input('cloud_preferences');
        $criterias = Criterion::all();
        $alternatives = Alternative::all();
        $description = $request->description;
        $paas = DB::table('paas')
                    ->join('iaas_paas','iaas_paas.paas_id','=','paas.id')
                    ->join('iaas','iaas_paas.iaas_id','=','iaas.id')
                    ->select('paas.id','paas.name')
                    ->whereIn('iaas.id',$request->input('cloud_preferences'))
                    ->distinct()
                    ->get();
        
        
        $check = Project::checkIfBothCloudsAreSelected($request->input('cloud_preferences'));

        if($check)
        {
           $ahps = Ahp::where('client_id','=',$client->id)->orWhere('client_id','=',0)->get();
        }
        else
        {
           $ahps = "Disabled";
        }
        
      
        return view('projects.add_paas',compact('client','paas','criterias','alternatives','ahps','project_name','description','cloud_preferences'));
    }

    public function store(Request $request,Client $client)
    {   
        $json = Project::createProjectJSON($request->cloud_preferences,$request->paas_preferences);
       
        //return array_keys($json['IaaS']);
        //return $json["PaaS"]["Apprenda"]["Supporting Languages"];
        
        return PaaS::getSupportedPaas1('Public',1001,$json);
        //$json["PaaS"]['Cloud Foundry']['Supporting Languages'];

        return getSupportedPaas1($firstCloud,$application_id,$json);

        return "success";

        $project = new Project;
        $project->name = $request->name;
        $project->client_id = $client->id;
        $project->description = $request->description;

        if($request->ahp == "Disabled")
        {   
            //Bypass Ahp model
            $project->ahp_id = 0;
            Project::createProjectJSON($request->cloud_preferences,$request->paas_preferences);
            $project->save();
            return redirect('/clients/'.$client->id.'/projects');
        }
        else if($request->type != 'New')
        { 
          //Attach alread created AHP Model
          $project->ahp_id = $request->type;
          Project::createProjectJSON($request->cloud_preferences,$request->paas_preferences);
          $project->save();
         
          return redirect('/clients/'.$client->id.'/projects');
        }
        else
        {   //create new AHP Model
            $project->ahp_id = $request->type;
            $ahp = new Ahp;
            $ahp->client_id = $client->id;
            $ahp->name = $request->name;
            Project::createProjectJSON($request->cloud_preferences,$request->paas_preferences);
            $ahp->save();  
            $ahp->criteria()->attach($request->input('criterias'));
            $ahp->alternatives()->attach($request->input('alternatives'));
            $project->ahp_id = $ahp->id;
            
        }

        $project->save();
        session()->put('clientId',$client->id);
        session()->put('clientName',$client->name);
        session()->put('projectName',$project->name);
         
        $count = 0; $crit=0;
        $all_alternatives = DB::table('ahp_alternative')->where('ahp_id', $project->ahp_id)->pluck('ap_id');
        $all_criteria = DB::table('ahp_criteria')->where('ahp_id', $project->ahp_id)->pluck('cp_id');

        foreach ($all_criteria as $criteria) 
        {
            for($i=0;$i<sizeof($all_alternatives);$i++)
            {
                for($j=0;$j<sizeof($all_alternatives);$j++)
                {
                    if($i==$j)
                    {
                          //  $a[$i][$j]=1;
                         DB::table('alternative_matrix')->insert(
                          ['ahp_id' => $project->ahp_id, 
                          'cp_id'=>$criteria,
                          'ap_id'=>$all_alternatives[$i],
                          'vs_ap_id'=>$all_alternatives[$i],
                          'value'=>1]
                          );
                    }
                    else if($i>$j)
                    {

                        //  $a[$i][$j] = (1/$a[$j][$i]);
                        DB::table('alternative_matrix')->insert(
                          ['ahp_id' => $project->ahp_id, 
                          'cp_id'=>$criteria,
                          'ap_id'=>$all_alternatives[$i],
                          'vs_ap_id'=>$all_alternatives[$j],
                          'value'=>0
                          ]);
                    }
                    else
                    {
                        // $a[$i][$j] = $all_alternative_values[$k];
                        // $k = $k+1;
                        DB::table('alternative_matrix')->insert(
                          ['ahp_id' => $project->ahp_id, 
                          'cp_id'=>$criteria,
                          'ap_id'=>$all_alternatives[$i],
                          'vs_ap_id'=>$all_alternatives[$j],
                          'value'=>0
                          ]);
                    }
                }
            }

            for ($i = 0; $i < sizeof($all_alternatives); $i++) 
            {
                 DB::table('alternative_ranking')->insert(
                  ['ahp_id' => $project->ahp_id, 
                  'cp_id'=> $criteria,
                  'ap_id'=>$all_alternatives[$i],
                  'normalize_1'=>0,
                  'normalize_2'=>0]
                  );
            }
        }
        
        $client->touch();
        return redirect('clients/project/criteria/'.$count.'/'.$project->id.'/'.$crit);

    }

    public function analyseProjectAlternatives(Request $request,$count,$project,$crit)
    { 
        $proj1 = Project::find($project);
        $all_alternatives =  DB::table('ahp_alternative')->where('ahp_id', $proj1->ahp_id)->pluck('alternative_id');
        $all_criteria = DB::table('ahp_criteria')->where('ahp_id', $proj1->ahp_id)->pluck('criteria_id');
        $cycle = $count + 1;
        $n = sizeof($all_alternatives);
        if($count == 0 and $crit == 0)
        {

        }
        else
        {
            $this->validate($request,['scale'=>'not_in:0']);
           
            $client = Client::where('id',$proj1->client_id)->get();

            session()->put('clientName',$client[0]->name);
            session()->put('clientId',$client[0]->id);
            session()->put('projectName',$proj1->name);

            $ap =session()->get('ap1');
            $vs_ap =session()->get('ap2');
            $cp =session()->get('cp');

            $ap_id =  DB::table('ahp_alternative')->where('ahp_id', $proj1->ahp_id)->where('alternative_id', $ap)->value('ap_id');
            $vs_ap_id =  DB::table('ahp_alternative')->where('ahp_id', $proj1->ahp_id)->where('alternative_id', $vs_ap)->value('ap_id');
            $cp_id = DB::table('ahp_criteria')->where('ahp_id', $proj1->ahp_id)->where('criteria_id', $cp)->value('cp_id');
              
            $critOption = $request->cloud;
            $critValue = $request->scale;
           
            if($critOption==$ap)
            {   
                DB::table('alternative_matrix')
                ->where('ahp_id',$proj1->ahp_id)
                ->where('cp_id',$cp_id)
                ->where('ap_id',$ap_id)
                ->where('vs_ap_id',$vs_ap_id)
                ->update(['value' =>$critValue]);

                DB::table('alternative_matrix')
                ->where('ahp_id',$proj1->ahp_id)
                ->where('cp_id',$cp_id)
                ->where('ap_id',$vs_ap_id)
                ->where('vs_ap_id',$ap_id)
                ->update(['value' =>(1/$critValue)]);
            }
            else if($critOption==$vs_ap)
            {

                DB::table('alternative_matrix')
                ->where('ahp_id',$proj1->ahp_id)
                ->where('cp_id',$cp_id)
                ->where('ap_id',$ap_id)
                ->where('vs_ap_id',$vs_ap_id)
                ->update(['value' =>(1/$critValue)]);

                DB::table('alternative_matrix')
                ->where('ahp_id',$proj1->ahp_id)
                ->where('cp_id',$cp_id)
                ->where('ap_id',$vs_ap_id)
                ->where('vs_ap_id',$ap_id)
                ->update(['value' =>$critValue]);
            }
        }

       

         $combination = Project::combinations($n,2);

        if($crit <  sizeof($all_criteria))
        {
            session()->forget('ap1');
            session()->forget('ap2');
            session()->forget('cp');

            if($count==0)
            {
                $alt1Id = $all_alternatives[$count];
                $alt1 = Alternative::find($alt1Id);
                $count = $count + 1;

                $alt2Id = $all_alternatives[$count];
                $alt2 = Alternative::find($alt2Id);
            }
            else
            {
                $rowLimit =  $count + 1;
                if($rowLimit<$combination)
                {
                    $alt1Id = $all_alternatives[$count];
                    $alt1 = Alternative::find($alt1Id);

                    $count = $count + 1;

                    $alt2Id = $all_alternatives[$count];
                    $alt2 = Alternative::find($alt2Id);

                }
                else
                {
                    $alt1Id = $all_alternatives[0];
                    $alt1 = Alternative::find($alt1Id);

                    $alt2Id = $all_alternatives[$count];
                    $alt2 = Alternative::find($alt2Id);
                }

            }

            if($cycle == $combination)
            {
                $count=0;
                $rowLimit=0;
                $cycle = 0;

                $criteriaId = $all_criteria[$crit];
                $criteria = Criterion::find($criteriaId);
                $crit = $crit + 1;
            }
            else
            {
                $criteriaId = $all_criteria[$crit];
                $criteria = Criterion::find($criteriaId);
            }
            session()->put('ap1',$alt1Id);
            session()->put('ap2',$alt2Id);
            session()->put('cp',$criteriaId);
           
            return view('projects.alternatives',compact('count','project','alt1','alt2','criteria','crit'));

        }
        else
        {
            $all_criteria = DB::table('ahp_criteria')->where('ahp_id', $proj1->ahp_id)->distinct()->pluck('cp_id');
            foreach ($all_criteria as $criteria)
            {
                $a = [[]];
                $all_alternative_values = DB::table('alternative_matrix')->where('ahp_id', $proj1->ahp_id)
                ->where('cp_id',$criteria)->orderBy('ap_id', 'asc')->pluck('value');

                $k=0;
                for($i=0;$i<sizeof($all_alternatives);$i++)
                {
                    for($j=0;$j<sizeof($all_alternatives);$j++)
                    {
                        if($i==$j)
                        {
                            $a[$i][$j]=$all_alternative_values[$k];
                        }
                        else if($i>$j)
                        {
                            $a[$i][$j] = $all_alternative_values[$k];
                        }
                        else
                        {
                            $a[$i][$j] = $all_alternative_values[$k];
                        }
                        $k = $k+1;
                    }

                }
                   
                //-------------------------------------Square of first matrix----------------------------------------
                $square_1 = [[]];
                $all_alternatives =  DB::table('ahp_alternative')->where('ahp_id', $proj1->ahp_id)->pluck('ap_id');

                $sum=0;
                for($c=0; $c<sizeof($all_alternatives); $c++)
                {
                    for($d=0; $d<sizeof($all_alternatives); $d++)
                    {   
                        for($k=0; $k<sizeof($all_alternatives); $k++)
                        {
                            $sum = $sum + $a[$c][$k] * $a[$k][$d];
                        }

                        DB::table('alternative_matrix')
                        ->where('ahp_id', $proj1->ahp_id)
                        ->where('cp_id',$criteria)
                        ->where('ap_id',$all_alternatives[$c])
                        ->where('vs_ap_id',$all_alternatives[$d])
                        ->update(['square_1' =>$sum,'iteration' => 1]);

                        $square_1[$c][$d] = $sum;
                        $sum = 0;
                    }
                }

                $comp=array();$sum=0;
                
                for ($row = 0; $row < sizeof($all_alternatives); $row++) 
                {
                    $comp[$row]=0;
                    for ($col = 0; $col < sizeof($all_alternatives); $col++)
                    {
                        $comp[$row]=$comp[$row]+$square_1[$row][$col];
                    }
                    $sum=$sum +  $comp[$row];
                }

                for ($col = 0; $col < sizeof($all_alternatives); $col++) 
                {
                    //Anurag echo "the value is: ";
                    DB::table('alternative_ranking')
                    ->where('ahp_id', $proj1->ahp_id)
                    ->where('cp_id',$criteria)
                    ->where('ap_id',$all_alternatives[$col])
                    ->update(['normalize_1' =>($comp[$col]/$sum)]);
                }

                //----------------------------------------Square_2 of result matrix---------------------------------------
                $square_2 = [[]];
                $sum=0;
                for($c=0; $c<sizeof($all_alternatives); $c++)
                {
                    for($d=0; $d<sizeof($all_alternatives); $d++)
                    {   
                        for($k=0; $k<sizeof($all_alternatives); $k++)
                        {
                            $sum = $sum +  $square_1[$c][$k] *  $square_1[$k][$d];
                        }

                        DB::table('alternative_matrix')
                        ->where('ahp_id', $proj1->ahp_id)
                        ->where('cp_id',$criteria)
                        ->where('ap_id',$all_alternatives[$c])
                        ->where('vs_ap_id',$all_alternatives[$d])
                        ->update(['square_2' =>$sum,'iteration' => 2]);

                        $square_2[$c][$d] = $sum;
                        $sum = 0;
                    }
                }

                $comp2=array();
                $sum=0;
                for ($row = 0; $row < sizeof($all_alternatives); $row++) 
                {
                    $comp2[$row]=0;
                    for ($col = 0; $col < sizeof($all_alternatives); $col++)
                    {
                        $comp2[$row]=$comp2[$row]+$square_2[$row][$col];
                    }
                    $sum=$sum +  $comp2[$row];
                }

                for ($col = 0; $col < sizeof($all_alternatives); $col++) 
                {
                    //Anurag echo "the value is: ";
                    DB::table('alternative_ranking')
                    ->where('ahp_id', $proj1->ahp_id)
                    ->where('cp_id',$criteria)
                    ->where('ap_id',$all_alternatives[$col])
                    ->update(['normalize_2' =>($comp2[$col]/$sum)]);

                }

            }

            $clientId = DB::table('projects')->where('id', $project)->value('client_id');
        }
        session()->flash('message','Project added successfully!');
        return redirect('/clients/'.$clientId.'/projects');
    }


    
}