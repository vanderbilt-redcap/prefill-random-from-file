<?php
namespace Vanderbilt\PrefillRandomFromFileExternalModule;

use ExternalModules\AbstractExternalModule;
use ExternalModules\ExternalModules;

class PrefillRandomFromFileExternalModule extends AbstractExternalModule
{
    function redcap_data_entry_form($project_id,$record,$instrument,$event_id,$group_id,$repeat_instance = 1) {
        $surveyView = $this->getProjectSetting('survey-view');
        if ($surveyView != 1) {
            $javaString = $this->generatePrefillJavascript();
            echo $javaString;
        }
    }

    function redcap_survey_page($project_id,$record,$instrument,$event_id,$group_id,$survey_hash,$response_id,$repeat_instance = 1) {
        $javaString = $this->generatePrefillJavascript();
        echo $javaString;
    }

    public function generatePrefillJavascript() {
        global $Proj;

        $fileSetting = $this->getProjectSetting('values-file');
        $prefillFields = $this->getProjectSetting('prefill-fields');
        $fileOpen = \Files::getEdocContentsAttributes($fileSetting);
        $fileData = $fileOpen[2];
        $splitFile = explode("\n",$fileData);
        $randomSentenceLine = array();
        $chosenLines = array();
        $calcText = "<script>
            $(document).ready(function() {";

            foreach ($splitFile as $index => $fileLine) {
                $fileLine = str_replace("\r","",$fileLine);
                if ($fileLine == "") continue;
                $randomSentenceLine[] = $fileLine;
            }
            for ($i = 0; $i < count($prefillFields); $i++) {
                if (($Proj->metadata[$prefillFields[$i]]['element_type'] != "text" && $Proj->metadata[$prefillFields[$i]]['element_type'] != "textarea") || empty($Proj->metadata[$prefillFields[$i]])) continue;
                $chosenLine = "";
                $loopCount = 0;
                do {
                    $randIndex = rand(0,count($randomSentenceLine) - 1);
                    $chosenLine = $randomSentenceLine[$randIndex];
                    $loopCount++;
                } while (in_array($chosenLine,$chosenLines) && $loopCount < 200);
                $chosenLines[$i] = $chosenLine;
                unset($randomSentenceLine[$randIndex]);
                $calcText .= "$(\"[name='".$prefillFields[$i]."']\").val('".json_encode($chosenLine,JSON_HEX_APOS)."').blur();";
            }
            $calcText .= "});
        </script>";
        return $calcText;
    }
}