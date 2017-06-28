<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use App\Platform;
class PaaS extends Model
{

    protected $table = 'paas';

    public function platforms()
    {
        return $this->belongsToMany(Platform::class, 'paas_platform','paas_id','platform_id')->withTimestamps();
    }

    public function iaas()
    {
        return $this->belongsToMany(IaaS::class, 'iaas_paas','paas_id','iaas_id')->withTimestamps();
    }

    public static function getSupportedPaas($firstCloud,$application_id)
    {

        $supported_paas_platforms=[];
        //$paas = PaaS::all();   

        /*$firstCloud =Alternative::join('application_alternative_ranking','application_alternative_ranking.alternative_id','=','alternatives.id')
                    ->where('application_alternative_ranking.project_id', $project->id)
                    ->where('application_alternative_ranking.application_id',$application->id)
                    ->first();*/
 
                 

        //    $name = $firstCloud->name;
        //return $name;
        if(strpos($firstCloud, 'Public') !== false)
        {
            $cloudType = "Public";
        }
        else
        {
            $cloudType = "Private";
        }
        
        $CloudSupportedPaas = PaaS::join('iaas_paas','iaas_paas.paas_id','=','paas.id')
                                  ->join('iaas','iaas_paas.iaas_id','=','iaas.id')
                                  ->select('paas.id','paas.name')
                                  ->where('iaas.type',$cloudType)
                                  ->groupBy('paas.id','paas.name')
                                  ->get();

        $application = Application::find($application_id);       
        foreach($application->platforms as $platform)
        {
            $application_platforms_array[] = $platform->id;
        }
                    
        foreach($CloudSupportedPaas as $paasid)
        {
            $pass_platforms=$paasid->platforms;
            $count=0;
            foreach($pass_platforms as $pass_platform)
            {
                foreach($application->platforms as $platform)
                {
                    if($pass_platform->pivot->platform_id == $platform->id)
                    {
                        $count = $count + 1;
                    }
                }
            }   
                                                            
            if($count == sizeof($application_platforms_array))
            {
                $supported_paas_platforms[] = $paasid->name;
            }                                           
        }                                       
        return $supported_paas_platforms;
    }



    public static function getSupportedPaas1($firstCloud,$application_id,$json)
    {
        $language_supported_paas_platforms=[];
        
        if(strpos($firstCloud, 'Public') !== false)
        {
            $cloudType = "Public";
        }
        else
        {
            $cloudType = "Private";
        }

        
        $application = Application::find($application_id);     
        //return $application->compliances->name;  
        foreach($application->platforms as $platform)
        {
            $application_platforms_array[] = $platform->id;
        }

        $project_paas = array_keys($json["PaaS"]);

        $project_cloud_supported_paas = PaaS::join('iaas_paas','iaas_paas.paas_id','=','paas.id')
                                  ->join('iaas','iaas_paas.iaas_id','=','iaas.id')
                                  ->select('paas.name')
                                  ->whereIn('paas.name',$project_paas)
                                  ->where('iaas.type',$cloudType)
                                  ->groupBy('paas.name')
                                  ->get();

        $global_paas_list=[];
        
        /*return 
        return $json["PaaS"][$project_cloud_supported_paas[0]->name]["Supporting Languages"];                         
*/      //echo $application->platforms;
        //return $json["PaaS"]['Apprenda']["Supporting Languages"]; 
       
       // echo $project_cloud_supported_paas;
        /*echo "---------------------------------------------";
        echo "the app platforms are ";
        echo $application->platforms;*/

        //PaaS Filtering for platform field
        foreach($project_cloud_supported_paas as $project_paas)
        {   
            $count=0;
            foreach($application->platforms as $platform)
            {
                //return $application->platforms;
               
                foreach($json["PaaS"][$project_paas->name]["Supporting Languages"] as $language)
                {
                    if($language == $platform->code)
                    {
                        $count = $count + 1;
                    }    
                    
                } 
                
            }

            if($count == sizeof($application->platforms))
            {
                $language_supported_paas_platforms[] = $project_paas->name;
            }
            else
            {   
                $global_paas_list[$project_paas->name] = "Application platform not supported";
            } 

        } 
        
        $proj_pass = [];
        foreach($project_cloud_supported_paas as $paas)
        {
            $proj_cloud_pass[] = $paas->name; 
        }
        $non_cloud_supported_paas = array_diff(array_keys($json["PaaS"]), $proj_cloud_pass);
        
      

        foreach($non_cloud_supported_paas as $paas)
        {
             $global_paas_list[$paas] = $paas." does not support ".$cloudType." Cloud";
        }


        echo "---------------------------------------------";
        echo "All paas platforms ".$project_cloud_supported_paas;
        echo "---------------------------------------------";
        var_dump($language_supported_paas_platforms);
       /* return $json["PaaS"]["Apprenda"]["IaaS"];   
        return $language_supported_paas_platforms;   */
        
        
        //PaaS filtering in terms of compliance
        $compliance_supported_paas_platforms=[];
        foreach($language_supported_paas_platforms as $paas_platform)
        {   
            
            echo "the pass is ".$paas_platform;
              
                
                //return $json['IaaS']['Amazon Web Services']['Compliance'];
                foreach($json["PaaS"][$paas_platform]["IaaS"] as $cloud)
                {   $count = 0;
                    foreach($json['IaaS'][$cloud]['Compliance'] as $project_compliance)
                    {   //$count = 0;
                        foreach($application->compliances as $compliance)
                        {
                            if($project_compliance == $compliance->name)
                            {
                                $count = $count + 1;
                                //echo $cloud;
                            }
                        }
                              
                    
                    }/*echo "the count for PaaS ".$paas_platform."for cloud ".$cloud." is ".$count;
                    echo "---------------------------------------------------------------------------------------------";*/

                    if($count == sizeof($application->compliances))
                    {   
                        $compliance_supported_paas_platforms[] = $paas_platform;
                    }
                    

                      
                } 
                    
        }
        $compliance_supported_paas_platforms_unique = array_unique($compliance_supported_paas_platforms) ;
        $non_compliant_pass = array_diff($language_supported_paas_platforms, $compliance_supported_paas_platforms_unique);
        foreach($non_compliant_pass as $paas)
        {
             $global_paas_list[$paas] = "Application compliance not supported";
        }
        var_dump($compliance_supported_paas_platforms_unique);
        echo "---------------------------------------------------------------------------------------------";

        //Data residency
        $data_residency_supported_cloud = [];
        foreach($compliance_supported_paas_platforms_unique as $paas_platform)
        {   $count = 0;
            foreach($json["PaaS"][$paas_platform]["IaaS"] as $cloud)
            {   
                /*if(in_array($application->country_name, $json["IaaS"][$cloud]["Country"]))
                {
                   $count = $count + 1;
                }*/
                if(in_array($application->country_name, $json["IaaS"][$cloud]["Country"]))
                {
                    $count = $count + 1;
                }
            }
            if($count > 0)
            {
                $data_residency_supported_cloud[] = $paas_platform;
                $global_paas_list[$paas_platform] = "PaaS platform can be supported";
            }
            else
            {
                $global_paas_list[$paas_platform] = "Application data residency not supported";

            }   
        }
        echo "---------------------------------------------------------------------------------------------";
          echo "---------------------------------------------------------------------------------------------";
          echo "---------------------------------------------------------------------------------------------";
          echo "---------------------------------------------------------------------------------------------";
          echo "---------------------------------------------------------------------------------------------";
          echo "---------------------------------------------------------------------------------------------";
          echo "---------------------------------------------------------------------------------------------";
          echo "---------------------------------------------------------------------------------------------";
           $project_paas = array_keys($json["PaaS"]);
          echo "the main list";
          var_dump($project_paas);
           echo "---------------------------------------------------------------------------------------------";
          echo "The cloud supported list";
          echo $project_cloud_supported_paas; 
         echo "---------------------------------------------------------------------------------------------";  
         echo "The platform supported list ";
        var_dump($language_supported_paas_platforms);
         echo "---------------------------------------------------------------------------------------------";
        echo "The compliance list";
         var_dump($compliance_supported_paas_platforms_unique);
      /*  echo "public cloud supported list".$project_cloud_supported_paas;
        
         echo "---------------------------------------------------------------------------------------------";
        echo "The platform supported list ";
        var_dump($language_supported_paas_platforms);
         echo "---------------------------------------------------------------------------------------------";
         echo "The compliance list";
         var_dump($compliance_supported_paas_platforms_unique);
          echo "---------------------------------------------------------------------------------------------";
          echo "data residency list";
          var_dump($data_residency_supported_cloud);
          echo "---------------------------------------------------------------------------------------------";
          var_dump($global_paas_list);*/
          echo "data residency list";
          var_dump($data_residency_supported_cloud);
          echo "---------------------------------------------------------------------------------------------";
          echo "---------------------------------------------------------------------------------------------";
          var_dump($global_paas_list);
          //return array_diff($language_supported_paas_platforms, $compliance_supported_paas_platforms_unique);
          return $global_paas_list;
       // return $data_residency_supported_cloud;
       /* foreach($language_supported_paas_platforms as $paas_platform)
        {   
            
            $count = 0;
            foreach($application->compliances as $compliance)
            {  
                $count = 0;
                //return $json['IaaS']['Amazon Web Services']['Compliance'];
                foreach($json["PaaS"][$paas_platform]["IaaS"] as $cloud)
                {   
                    foreach($json['IaaS'][$cloud]['Compliance'] as $project_compliance)
                    {
                        if($project_compliance == $compliance->name)
                        {
                            $count = $count + 1;
                            //echo $compliance->name;
                        }
                              
                    
                    }//echo $count; 
                } 
                
            }echo "the count for PaaS ".$paas_platform." is ".$count;

            if($count == sizeof($application->compliances))
            {
                $language_supported_paas_platforms[] = $project_paas->name;
            } 

        } */

        

    }

}
