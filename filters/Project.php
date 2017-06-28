<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use App\Project;
use App\Application;
use App\PaaS;
use App\SaaS;
use App\Ahp;
use \PDF;
use App\IaaS;
use App\Country;

class Project extends Model
{
    //
    public function client()
    {
    	return $this->belongsTo(Client::class);
    }
    public function applications()
    {
    	return $this->belongsToMany(Application::class, 'application_project')->withPivot('disposition_1','disposition_2','disposition_3','cloud_suitability_1','cloud_suitability_2','cloud_suitability_3','platform_1','platform_2','platform_3','completed','rules')->withTimestamps();
    }

    public function iaas()
    {
        return $this->belongsToMany(IaaS::class, 'iaas_project','project_id','iaas_id')->withTimestamps();
    }

    public function criteria()
    {
    	return $this->belongsToMany(Criterion::class, 'criteria_project','project_id','criteria_id')->withTimestamps();
    }

    public function alternatives()
    {
        return $this->belongsToMany(Alternative::class, 'alternative_project','project_id','alternative_id')->withTimestamps();
    }

    public function ahp()
    {
        return $this->belongsTo(Ahp::class);
    }
    
    public function alternative_matrix()
    {
        return $this->belongsToMany(Project::class,'alternative_matrix')->withPivot('value');
    }

    public static function createProjectJSON($selected_iaas,$selected_paas)
    {
        $proj=array();
        $proj["IaaS"]=array();

        foreach($selected_iaas as $ia)
        {
            $iaas = IaaS::find($ia);
            $proj["IaaS"][$iaas->name]=array();
            $proj["IaaS"][$iaas->name]['Type']=$iaas->type;
            $proj["IaaS"][$iaas->name]['Compliance']=array();
            $proj["IaaS"][$iaas->name]['Country']=array();
            
            //return $iaas->compliances;
            foreach($iaas->compliances as $compliance)
            {
              array_push($proj["IaaS"][$iaas->name]['Compliance'],$compliance->name);
            }
            foreach($iaas->countries as $country)
            {
              array_push($proj["IaaS"][$iaas->name]['Country'],$country->name);
            }


            /*$proj["IaaS"][$iaas->name]['Country']=array();

            $country;
            foreach($iaas->countries as $country)
            {
                $country= $country->name;

                //array_push($proj["IaaS"][$iaas->name]['Country'],$country->name);
            }
            return $country;*/
            
        }
        $proj["PaaS"]=array();
        foreach($selected_paas as $pa)
        {
            $paas=PaaS::find($pa);
            $proj["PaaS"][$paas->name]=array();
            $proj["PaaS"][$paas->name]['Supporting Languages']=array();
            foreach($paas->platforms as $platform)
            {
                array_push($proj["PaaS"][$paas->name]['Supporting Languages'],$platform->code);
            }
            $proj["PaaS"][$paas->name]['IaaS']=array();
            foreach($paas->iaas as $iaas)
            {
                array_push($proj["PaaS"][$paas->name]['IaaS'],$iaas->name);
            }
        }
        return $proj;
    }

    public static function checkIfBothCloudsAreSelected($clouds)
    {
       $var =  DB::table('iaas')
                ->select('type')
                ->whereIn('id',$clouds)
                ->distinct()
                ->get();

        if(sizeof($var) == 1){
            return false;
        }
        else{
            return true;
        }
    }


    public static function factorial($n) 
    {
        if ($n <= 1) 
        {
            return 1;
        } 
        else 
        {
            return Project::factorial($n - 1) * $n;
        }
    }

    public static function combinations($n, $k) 
    {
       //note this defualts to 0 if $n < $k
        if ($n < $k) 
        {
            return 0;
        } 
        else 
        {
            return Project::factorial($n)/(Project::factorial($k)*Project::factorial(($n - $k)));
        }
    }

    public static function arraycount($array, $value)
    {
        $counter = 0;
        foreach($array as $thisvalue) 
        {
            if($thisvalue === $value)
            { 
                $counter++; 
            }
        }
        return $counter;
    }

    public static function getAttributeRules($application_id)
    {
        $rules=array();
        $appList= DB::table('applications')->where('id', $application_id)->get();

        foreach($appList as $app)
        {
            if($app->service_level=='AVAI')
            {
                $rules[]=39;
            }
            elseif($app->service_level=='PERF')
            {
                $rules[]=40;
            }
            elseif($app->service_level=='BOTH')
            {
                $rules[]=41;
            }
            elseif($app->service_level=='NONE')
            {
                $rules[]=42;
            }
            
            if($app->business_classification=='MISCRI')
            {
                $rules[]=43;
            }
            elseif($app->business_classification=='BUSICRI')
            {
                $rules[]=44;
            }
            elseif($app->business_classification=='BUSIIMP')
            {
                $rules[]=45;
            }
            elseif($app->business_classification=='PRODIMP')
            {
                $rules[]=46;
            }
            elseif($app->business_classification=='NONCRI')
            {
                $rules[]=47;
            }
            if($app->business_impact=='LARGE_FIN_COMP')
            {
                $rules[]=48;
            }
            elseif($app->business_impact=='SOME_FIN_PROD')
            {
                $rules[]=49;
            }
            elseif($app->business_impact=='SOME_PROD')
            {
                $rules[]=50;
            }

            if($app->data_confidentiality=='NO_CONFIDENTIALITY_REQUIREMENT')
            {
                $rules[]=51;
            }
            elseif($app->data_confidentiality=='NOT_OUTSIDE_ENT_FIREWALL')
            {
                $rules[]=52;
            }
            elseif($app->data_confidentiality=='APPROVED_PUBLIC_DATACENTER')
            {
                $rules[]=53;
            }

            if($app->workload_variation=='NO_VARIATIOn')
            {
                $rules[]=54;
            }
            elseif($app->workload_variation=='PREDICTABLE')
            {
                $rules[]=55;
            }
            elseif($app->workload_variation=='UNPREDICTABLE')
            {
                $rules[]=56;
            }

            if($app->latency_sensitivity=='LOW')
            {
                $rules[]=57;
            }
            elseif($app->latency_sensitivity=='MEDIUM')
            {
                $rules[]=58;
            }
            elseif($app->latency_sensitivity=='HIGH')
            {
                $rules[]=59;
            }
            elseif($app->latency_sensitivity=='NONE')
            {
                $rules[]=60;
            }

            if($app->scalability=='HORIZONTAL')
            {
                $rules[]=61;
            }
            elseif($app->scalability=='VERTICAL')
            {
                $rules[]=62;
            }
            elseif($app->scalability=='MIXED')
            {
                $rules[]=63;
            }
            elseif($app->scalability=='NA')
            {
                $rules[]=64;
            }

            if($app->extensibility=='MONOLITH')
            {
                $rules[]=65;
            }
            elseif($app->extensibility=='INTERFACES')
            {
                $rules[]=66;
            }
            elseif($app->extensibility=='SOA')
            {
                $rules[]=67;
            }
            elseif($app->extensibility=='NA')
            {
                $rules[]=68;
            }

            if($app->io_intensity=='LOW')
            {
                $rules[]=69;
            }
            elseif($app->io_intensity=='MEDIUM')
            {
                $rules[]=70;
            }
            elseif($app->io_intensity=='HIGH')
            {
                $rules[]=71;
            }
            elseif($app->io_intensity=='NA')
            {
                $rules[]=72;
            }
        }
        return $rules;
    }

    public static function getAppliedRules($Project,$Application)
    {

        $rules = array();
        $apps = DB::table('application_project')
                ->where('project_id',$Project->id)
                ->where('application_id',$Application->id)
                ->get();

        foreach($apps as $app)
        {
            if($app->disposition_1=="Retire")
            {
                if($Application->stage=='EOL' and $Application->eoldriver=='BU')
                {
                    $rules[]=101;
                }
            }

            if($app->disposition_1=="Retain")
            {
                if($Application->stage=='AEOL' and $Application->eoldriver=='BU')
                {
                    $rules[]=201;
                }

                if($Application->type=='DESKTOP')
                {
                    if($Application->stage=='NEW')
                    {
                        $rules[]=202;
                    }
                    if($Application->development_responsibility=='COTS')
                    {
                        $rules[]=203;
                    }  
                }

                foreach($Application->hardware as $hw)
                {
                    if($hw->code=="RISC")
                    {
                        $rules[]=204;
                    }
                    if($hw->code=="MAINFRAME")
                    {
                        $rules[]=205;
                    }
                }
                foreach($Application->operating_systems as $os)
                {
                    if($os->code == "OTHER")
                    {
                        $rules[]=206;       
                    }
                }

                foreach($Application->dependencies as $dependency)
                {
                    if($dependency->name == "Hardware Dependent")
                    {
                        $rules[]=207;    
                    }
                }
            }
            
            if($app->disposition_1=="Develop")
            {  
                if(($Application->stage=='EOL' or $Application->stage=='AEOL') and $Application->eoldriver=='TE')
                {
                    $rules[]=301;
                    //Check if source code is availabel which could help understand the logic et all
                }
                if($Application->stage == 'EUMN' or $Application->stage=="EMAN")
                {
                    if($Application->type == 'DESKTOP')
                    {
                        $rules[]=302;

                        //Mention that managed existing applicaiton has the possibility fo functional and technical documentation availabel to understand the functionality and assist in the REDEVELOPMENT
                    }
                }
                //return 1;
                if($Application->service_level!='NONE')
                {
                    $rules[]=303;
                }
            }

            if($app->disposition_1=="Migrate")
            {
                if($Application->stage=='NEW' or $Application->stage=='EUMN' or $Application->stage=='EMAN')
                {
                    $rules[]=401; 
                } 

                if($Application->type!='Desktop')
                {
                    if($Application->development_responsibility=='COTS')
                    {
                        $rules[]=402;
                    }
                    else
                    {
                        $software_dependency = 0;
                        foreach($Application->dependencies as $dependency)
                        {
                            if($dependency->name == 'Operating System Dependent' or $dependency->name == 'Operating Environment Dependent')
                            {
                                $software_dependency = 1;  
                            }
                        }
                        if($Application->source_code_available==0)
                        {
                            $rules[]=403;

                            if($Application->stage=='NEW' or $Application->stage=='EUMN' or $Application->stage=='EMAN')
                            {
                                $rules[]=404; 
                            } 
                        }
                        else if($software_dependency == 1)
                        {
                            $rules[]=406;   
                        }
                        else
                        {
                            //NON COTS AND WEB BASED APPLICATIONS
                            $supportedPaas = PaaS::getSupportedPaas($app->cloud_suitability_1,$Application->id);
                            if(empty($supportedPaas))
                            {
                                $rules[]=410; 
                            }
                        }   
                    }

                    if($Application->service_level!='NONE')
                    {
                        //NON-DESKTOP APPLICATIONS
                        $rules[]=405;
                    }

                    if($Application->business_classification != 'NONCRI')
                    {
                        //NON-DESKTOP APPLICATIONS
                        $rules[]=406;
                    }
                    if($Application->scalability == 'HORIZONTAL')
                    {
                        //RULE TEXT PENDING
                        $rules[]=407;
                    }
                    if($Application->scalability == 'VERTICAL')
                    {
                        
                        $rules[]=408;
                    }
                    if($Application->scalability == 'MIXED')
                    {
                        
                        $rules[]=409;
                    }
                    
                }  
            }

            if($app->disposition_1=="Modernise")
            {
                if($Application->stage=='NEW' or $Application->stage=='EUMN' or $Application->stage=='EMAN')
                {
                    $rules[]=501; 
                }

                if($Application->source_code_available==1)
                {
                    $supportedPaas = PaaS::getSupportedPaas($app->cloud_suitability_1,$Application->id);
                    if(!empty($supportedPaas))
                    {
                        $rules[]=502;
                    }    
                }
                if($Application->service_level!='NONE')
                {
                    //NON-DESKTOP APPLICATIONS
                    $rules[]=503;

                    //WORK FROM HERE
                    /* THIS RULE AND SIMILAR ONE IN MIGRATE SHOULD READ LIKE THIS
•   It’s a Revenue Generating application with Contracted SLA for Availability and Performance. Such SLA’s can be ensured by deploying the application in multiple regions and/or availability zones featured by public cloud providers.
Leverage monitoring service from both IaaS provider and PaaS and additional 3rd party tools e.g. new relic, dynatrace.

                    */
                }
                if($Application->business_classification != 'NONCRI')
                {
                        //NON-DESKTOP APPLICATIONS
                        $rules[]=504;
                }
            }
        }

        $serialised_rules = json_encode($rules);       
        return $serialised_rules;        
    }

    public static function getAppliedRules_backup($Project,$Application)
    {
        $rulesattribute=array();
        $rules=array();
        $rules1="";
        

        $app= DB::table('application_project')
                ->where('project_id',$Project->id)
                ->where('application_id',$Application->id)
                ->get();
        foreach($app as $ap)
        {
          
        if($ap->disposition_1=="Retire")
        {

           if($Application->stage=='EOL' and $Application->eoldriver=='BU')
           {
            $rules[]=1;
           }
        }

        if($ap->disposition_1=="Retain")
        {  
           if($Application->stage=='AEOL' and $Application->eoldriver=='BU')
           {
           $rules[]=4;
           }
           if($Application->type=='DESKTOP')
           {
              if($Application->stage=='NEW')
              {
              $rules[]=17;
              }
              if($Application->development_responsibility=='COTS')
              {
              $rules[]=7;
              }
            }
            foreach($Application->hardware as $hw)
            {
              if($hw->code=="RISC")
              {
                $rules[]=19;
              }
              if($hw->code=="MAINFRAME")
              {
              $rules[]=20;
              }
              if($Application->development_responsibility=='COTS' and $Application->type=='DESKTOP')
              {
              $rules[]=7;
              }

            }
            foreach($Application->operating_systems as $os)
            {
                if($os->code == "OTHER")
                {
                    $rules[]=80;       
                }
            }
            
            foreach($Application->dependencies as $dependency)
            {
               if($dependency->name == "Hardware Dependent")
              {
                $rules[]=21;
  
                if($Application->development_responsibility=='COTS' and $Application->type=='DESKTOP')
               {
                $rules[]=7;
               }
              }

            }
        }
       if($ap->disposition_1=="Develop")
       {
            if($Application->stage=='EOL' and $Application->eoldriver=='TE')
            {
              $rules[]=8;
              $rules[]=9;
            }
            if($Application->stage=='AEOL' and $Application->eoldriver=='TE')
            {
            $rules[]=10;
            $rules[]=9;
            }
            if($Application->stage == 'EUMN' or $Application->stage=="EMAN")
            {
               if($Application->type == 'DESKTOP')
               {
               $rules[]=11;
               $rules[]=9;
               }
            }
        }

        if($ap->disposition_1=="Migrate")
        {
           /*if(empty($supportedPaas))
           {
            
           }*/
           if($Application->type!='Desktop')
           {
            if($Application->development_responsibility=='COTS')
            {
              $rules[]=12;
            
            }
            else
            {
               if($Application->source_code_available==0)
               {
               $rules[]=13;
               }
               else
               {
                 if(empty($supportedPaas))
                 {
                  $rules[]=23;
                  
                 }
               }

            }
           }

        }

        if($ap->disposition_1=="Modernise")
        {
           $supportedPaas = PaaS::getSupportedPaas($ap->cloud_suitability_1,$Application->id);
           if(!empty($supportedPaas))
               {
                          if($Application->stage=='NEW')
                           {
                            $rules[]=33;
                          }
                          elseif($Application->stage=='EUMN')
                          {
                            $rules[]=73;
                          }
                           elseif($Application->stage=='EMAN')
                          {
                            $rules[]=78;
                          }
                          
                          if($ap->platform_1=='Apprenda')
                          {
                            $rules[]=25;
                          }
                          elseif($ap->platform_1=='Cloud Foundry')
                          {
                            $rules[]=26;
                          }
                          elseif($ap->platform_1=='Open Shift')
                          {
                            $rules[]=27;
                          }
                          elseif($ap->platform_1=='Azure Cloud Services')
                          {
                            $rules[]=28;
                          }
                          elseif($ap->platform_1=='SharePoint Online')
                          {
                            $rules[]=29;
                          }
                          elseif($ap->platform_1=='Google App Engine')
                          { 
                            $rules[]=30;
                          }
                          elseif($ap->platform_1=='AWS Elastic Beanstalk')
                          {
                            $rules[]=31;
                          }
                          elseif($ap->platform_1=='IBM Bluemix')
                          {
                            $rules[]=32;
                          }
                          
                          $rules[]=34;
                          $rules[]=35;

                          if($ap->cloud_suitability_1=='Public Cloud')
                          {
                            $rules[]=36;
                          }
                          elseif($ap->cloud_suitability_1=='Private Cloud')
                          {
                            $rules[]=37;
                          }
                          elseif($ap->cloud_suitability_1=='Hybrid Cloud')
                          {
                            $rules[]=38;
                          }

                }
        }
     

        if($ap->disposition_1=='Develop' or $ap->disposition_1=='Migrate' or $ap->disposition_1=='Modernise')
        {
          $rulesattribute=Project::getAttributeRules($Application->id);
          $arraymerge=array_merge($rules,$rulesattribute);
          $rules1 = json_encode($arraymerge);
        }
        else 
        {
          $rules1 = json_encode($rules);       
        }
    }      
          return $rules1;
    }

   /* public static function getScreenStatus1($app,$proj){

        $ahp_completed = DB::table('application_project')->where('application_id',$app->id)->where('project_id',$proj->id)->value('completed');
        $status = "From_Start";  
        if(!empty($ahp_completed))
        {
            if($ahp_completed == '1')
            {
                //set session variable
                $ahp_appid = session()->get('ahp_appid');
                $ahp_projid = session()->get('ahp_projid');
                
                if($ahp_appid == '' and $ahp_projid == '')
                {
                    session()->put('ahp_appid',$app->id);
                    session()->put('ahp_projid',$proj->id);
                }
                else
                {
                    
                    if($ahp_appid == $app->id  and   $ahp_projid == $proj->id)
                    {
                        $status = "Continue"; 
                    }
                    else
                    {
                        session()->put('ahp_appid',$app->id);
                        session()->put('ahp_projid',$proj->id);
                        $status = "From_Start"; 
                    }
                }
            }
            else if($ahp_completed == '2')
            {
                //ahp already completed page
                $status = "Load_view"; 
            }
            else
            {
                //application not yet analysed
                $status = "Load_view";
            }
        }
        else
        {
            //application not yet associated
             $status = "Load_view";
        }  
        return $status;
    }*/

    public static function isApplicationAssociated($appid,$projid)
    {
        $query = DB::table('application_project')->where('application_id',$appid)->where('project_id',$projid)->value('application_id');
        
        if(!empty($query))
        {
           return true; 
        }
        else
        {
            return false;
        }

    }

    public function isAhpComplete($appid)
    {
        $isAhpComplete = DB::table('application_project')->where('application_id',$appid)->where('project_id',$this->id)->value('completed');
        
        if($isAhpComplete == 2)
        {
           return true; 
        }
        else
        {
           return false;
        }

    }

    public function getAhpStatus($appid)
    {   
        $ahpStatus = DB::table('application_project')->where('application_id',$appid)->where('project_id',$this->id)->value('completed');
        
        return $ahpStatus;
    }

    public static function setAhpAppSession($appid)
    {
       return session()->put('ahp_appid',$appid);
    }

    public static function getAhpAppSession()
    {
        return session()->get('ahp_appid');
    }

    public static function setAhpProjSession($projid)
    {
        return session()->put('ahp_projid',$projid);
    }

    public static function getAhpProjSession()
    {
        return session()->get('ahp_projid');
    }

    public static function getScreenStatus($appid,$projid)
    {
        $status = "From_Start"; 
        if(Project::isApplicationAssociated($appid,$projid)){
             
            $project = Project::find($projid);
            
            if(!($project->isAhpComplete($appid)))
            {
                $ahp_status = $project->getAhpStatus($appid);
                if($ahp_status == '1')
                {
                    $ahp_appid = Project::getAhpAppSession();
                    $ahp_projid = Project::getAhpProjSession();

                    if($ahp_appid == '' and $ahp_projid == '')
                    {
                        Project::setAhpAppSession($appid);
                        Project::setAhpProjSession($project->id);
                    }
                    else
                    {
                        
                        if($ahp_appid == $appid  and   $ahp_projid == $project->id)
                        {
                            $status = "Continue"; 
                        }
                        else
                        {
                            Project::setAhpAppSession($appid);
                            Project::setAhpProjSession($project->id);
                            $status = "From_Start"; 
                        }
                    }

                }
                else
                {
                    $status = "Load_view";
                }
            }
            else
            {
                $status = "Load_view";
            }
        }
        else
        {
            $status = "Load_view";
        }

        return $status;
    }


    public static function generateReport(Project $project,Application $application,$val)
        {
              $saas=SaaS::all();
              $criteriaId = DB::table('ahp_criteria')->where('ahp_id', $project->ahp_id)->pluck('criteria_id');

              for($i=0;$i<sizeof($criteriaId);$i++)
                  {
                      $ahpCriteriaData = DB::table('alternative_ranking')
                      ->join('ahp_alternative','alternative_ranking.ap_id','=','ahp_alternative.ap_id')
                      ->join('ahp_criteria','alternative_ranking.cp_id','=','ahp_criteria.cp_id')
                      ->join('alternatives','ahp_alternative.alternative_id','=','alternatives.id')
                      ->join('criteria','ahp_criteria.criteria_id','=','criteria.id')
                      ->select('alternative_ranking.cp_id','criteria.name as criteria_name','alternative_ranking.ap_id','alternatives.name','alternative_ranking.normalize_2')
                      ->where('ahp_alternative.ahp_id', $project->ahp_id)
                      ->where('criteria.id',$criteriaId[$i])
                      ->orderBy('alternative_ranking.cp_id','asc')
                      ->orderBy('alternative_ranking.normalize_2','desc')
                      ->get();
                      $allAhp[] = $ahpCriteriaData;
                  }

              $ahpCriteriaMatrix =  DB::table('application_criteria_matrix')
              ->select('cp_id','vs_cp_id','value')
              ->where('project_id', $project->id)
              ->where('application_id', $application->id)
              ->whereColumn('cp_id', '<>', 'vs_cp_id')
              ->whereColumn('cp_id', '<', 'vs_cp_id')
              ->get();

              $ahpCriteriaRank=[];
              foreach($ahpCriteriaMatrix as $data)
              {
                  $cp_name = DB::table('criteria')
                  ->join('ahp_criteria','ahp_criteria.criteria_id','=','criteria.id')
                  ->where('ahp_criteria.cp_id',$data->cp_id)
                  ->value('criteria.name');

                  $vs_cp_name = DB::table('criteria')
                  ->join('ahp_criteria','ahp_criteria.criteria_id','=','criteria.id')
                  ->where('ahp_criteria.cp_id',$data->vs_cp_id)
                  ->value('criteria.name');

                  if($data->value < 1)
                  {
                      
                      $value = DB::table('application_criteria_matrix')
                      ->where('cp_id',$data->vs_cp_id)
                      ->where('vs_cp_id',$data->cp_id)
                      ->where('project_id', $project->id)
                      ->where('application_id', $application->id)
                      ->value('value');

                      $ahpCritName[] = $cp_name;
                      $ahpCritName[] = $vs_cp_name;
                      $ahpCritName[] = $data->value;
                      $ahpCritName[] = $value;
                  }
                  else
                 {
                      $value = $data->value;
                      $ahpCritName[] = $cp_name;
                      $ahpCritName[] = $vs_cp_name;
                      $ahpCritName[] = $data->value;
                      $ahpCritName[] = round($value);
                 }
                 $ahpCriteriaRank[] = $ahpCritName;
                 unset($ahpCritName);
              }

              $cloudRanking = DB::table('application_alternative_ranking')
              ->join('alternatives','application_alternative_ranking.alternative_id','=','alternatives.id')
              ->where('project_id', $project->id)
              ->select('alternatives.name','application_alternative_ranking.weightage')
              ->where('application_id', $application->id)
              ->orderBy('application_alternative_ranking.weightage','desc')
              ->get();
      
              foreach ($project->applications as $ap) 
              {
                  if($ap->id == $application->id)
                  {
                      break;
                  }
              }
      
              if($ap->pivot->disposition_1 == "Retain" or $ap->pivot->disposition_1 == "Retire")
              {
                  unset($allAhp);
                  unset($ahpCriteriaRank);
                  unset($cloudRanking);
              }
              else if($ap->pivot->disposition_1 == "Develop")
              {
                  $supportedPaas[] = "Cloud Foundry";
              }
              else
              {
                  $supportedPaas = PaaS::getSupportedPaas($cloudRanking,$application->id);
              }

              if($val=="pdfreport")
              {
                  $header = \View::make('layouts.pdfheader')->render();
                  $footer = \View::make('layouts.pdffooter',compact('project','application'))->render();
                  $pdf = PDF::loadView('pdf.application', compact('project','application','allAhp','ahpCriteriaRank','cloudRanking','saas','supportedPaas'))
                  ->setOption('header-html',$header)
                  ->setOption('footer-html',$footer);
                  return $pdf;
              }
              elseif($val=="viewreport")
            {
                  $view =\View::make('pdf.applicationview', compact('project','application','allAhp','ahpCriteriaRank','cloudRanking','saas','supportedPaas'))->render();
                  return $view;  
              }
        }
}
