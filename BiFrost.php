<?php
class BiFrost
{

    protected $oBifrost;
    protected $aConfig = array();
    protected $aPackage = array();
    protected $aError = array();
    private $oQuery;
    private $aResult;

	public function __construct($aConfig = false) {
        if(!$aConfig) {
            return false;
        } else {
            $this->aConfig = $aConfig;
        }

        if($aInfo = parse_ini_file($this->aConfig['incs']['config'].'bifrost.ini')) {
            $this->oBifrost = new mysqli($aInfo['host'],$aInfo['username'],$aInfo['password'],$aInfo['dbname'],$aInfo['port']);
            if ($this->oBifrost->connect_error) {
                $this->storeError(FALSE,'Failed to connect to MySQL - '.$this->oBifrost->connect_error);
            }
            $this->oBifrost->set_charset($aInfo['charset']);
        } else {
            return false;
        }
    }

    public function prepQuery($aQuery) {
        $sQuery = $aQuery['query'];
        if($this->oQuery = $this->oBifrost->prepare($sQuery)) {
            if(array_key_exists('params', $aQuery)) {
                $aParams = $aQuery['params'];
                $sTypes = '';
                foreach($aParams as $iK=>$uParam) {
                    $sTypes .= $this->getType($uParam);
                }
                $this->oQuery->bind_param($sTypes,...$aParams);
                if ($this->oQuery->errno) {
                    $this->storeError(FALSE,'Unable to process MySQL query (check your params) - '.$this->oQuery->error);
                }
            }
            $this->oQuery->execute();
        } else {
            $this->storeError(FALSE,'Unable to prepare MySQL statement (check your syntax) - '.$this->oBifrost->error);
        }
        return $this;
    }

    public function fetchRow() {
        if(empty($this->aError)) {
            $aParams = [];
            $aRow = [];
            $oMeta = $this->oQuery->result_metadata();
            while($oField = $oMeta->fetch_field()) {
                $aParams[] = &$aRow[$oField->name];
            }
            $this->oQuery->bind_result(...$aParams);
            $aResult = [];
            while($this->oQuery->fetch()) {
                foreach($aRow as $sKey=>$uVal) {
                    $aResult[$sKey] = $uVal;
                }
            }
            if(empty($aResult)) {
                $this->aPackage['status'] = true;
                $this->aPackage['payload'] = 'No matching records found';
                $this->aPackage['rowcount'] = $this->getNumRows();
                return $this->aPackage;
            }
            $this->aPackage['status'] = true;
            $this->aPackage['payload'] = $aResult;
            $this->aPackage['rowcount'] = $this->getNumRows();
            return $this->aPackage;
        } else {
            return $this->getError();
        }
    }

    public function fetchRows() {
        if(empty($this->aError)) {
            $aParams = [];
            $aRow = [];
            $oMeta = $this->oQuery->result_metadata();
            while($oField = $oMeta->fetch_field()) {
                $aParams[] = &$aRow[$oField->name];
            }
            $this->oQuery->bind_result(...$aParams);
            $aResult = [];
            $iCount = 0;
            while($this->oQuery->fetch()) {
                foreach($aRow as $sKey=>$uVal) {
                    $aResult[$iCount][$sKey] = $uVal;
                }
                $iCount = $iCount+1;
            }
            if(empty($aResult)) {
                $this->aPackage['status'] = true;
                $this->aPackage['payload'] = 'No matching records found';
                $this->aPackage['rowcount'] = $this->getNumRows();
                return $this->aPackage;
            }
            $this->aPackage['status'] = true;
            $this->aPackage['payload'] = $aResult;
            $this->aPackage['rowcount'] = $this->getNumRows();
            return $this->aPackage;
        } else {
            return $this->getError();
        }
    }

    public function getNumRows() {
        if(empty($this->aError)) {
            return $this->oQuery->num_rows;
        } else {
            return $this->getError();
        }
    }

    public function getAffectedRows() {
        if(empty($this->aError)) {
            return $this->oQuery->affected_rows;
        } else {
            return $this->getError();
        }
    }

    public function getLastInsert() {
        return $this->oBifrost->insert_id;
    }
    
    public function getError() {
        return $this->aError;
    }

    public function closeBifrost() {
        $this->oBifrost->close();
    }

    protected function storeError($bError,$sError) {
        $this->aError = [
            "status"    => $bError,
            "payload"   => $sError];
    }

	private function getType($var) {
	    if (is_string($var)) return 's'; // String
	    if (is_float($var)) return 'd'; // Double
        if (is_int($var)) return 'i'; // Int
	    return 'b'; // Blob
	}

}
?>