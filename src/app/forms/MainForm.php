<?php
namespace app\forms;

use php\lang\Thread;
use app\forms\Load;
use app\forms\MainForm;
use php\gui\UXCheckbox;
use php\sql\SqlStatement;
use Exception;
use php\gui\framework\AbstractForm;
use php\gui\event\UXEvent; 
use php\gui\event\UXMouseEvent; 


class MainForm extends AbstractForm
{
    $ids=array(0,1,2,3,4,5,6,7,8,9,10,11,12,13,14,15,16,17,18);
    $ALL;
    $Rub=array('USD','Eur','CHF','GBP', 'Гривна','JPY',
                'тенге','лиры (TL)','Южнокарейская Вона', 'Юань', 'индийская рупия',
                'Нефть','Золото', 'Аллюминий', 'Платина', 'Серебро',
                'Акция Газпрома', 'Автоваз','Сбербанк');
    function erase_I($arr,$i){
        $res=array();
        for ($j=0;$j<count($arr);$j++){
            if ($j==$i)
                continue;
            array_push($res,$arr[$j]);    
        }
        return $res;
    }
    function UpdateMoney(){
        $money=array(
            0=>'https://news.yandex.ru/quotes/region/1.html',
            1=>'https://news.yandex.ru/quotes/region/23.html',
            2=>'https://news.yandex.ru/quotes/region/10007.html',
            3=>'https://news.yandex.ru/quotes/region/24.html',
            4=>'https://news.yandex.ru/quotes/region/10006.html',
            5=>'https://news.yandex.ru/quotes/region/25.html',
            6=>'https://news.yandex.ru/quotes/region/10009.html',
            7=>'https://news.yandex.ru/quotes/region/10011.html',
            8=>'https://news.yandex.ru/quotes/region/10019.html',
            9=>'https://news.yandex.ru/quotes/region/10018.html',
            10=>'https://news.yandex.ru/quotes/region/10020.html',
            11=>'https://news.yandex.ru/quotes/1006.html',
            12=>'https://news.yandex.ru/quotes/10.html',
            13=>'https://news.yandex.ru/quotes/1500.html',
            14=>'https://news.yandex.ru/quotes/1505.html',
            15=>'https://news.yandex.ru/quotes/1506.html',
            16=>'https://news.yandex.ru/quotes/29.html',
            17=>'https://news.yandex.ru/quotes/64.html',
            18=>'https://news.yandex.ru/quotes/60.html'
        );
        $X=600;
        $Y=88;
        //UXCheckbox->on('button.click',$this->CLK);
        //UXCheckbox->on
         $f=function(UXMouseEvent $event = null){
             $check=$event->sender;
             $txt=$check->text;
             $i=0;
             for ($i=0;$i<count($this->ALL);$i++){
                 if (strcmp($txt,$this->Rub[$this->ALL[$i]])==0){
                     if ($check->selected){
                         array_push($this->ids,$i);
                         sort($this->ids);
                         $this->UpdateKurse();
                         return;
                     } else {  
                         for ($j=0;$j<count($this->ids);$j++){
                             if ($this->ids[$j]==$this->ALL[$i]){
                                 //array_splice($this->ids,$j,1);
                                 $this->ids=$this->erase_I($this->ids,$j);
                                 $this->UpdateKurse();
                                 return;
                             }
                         }              
                     }
                 }
             }
         };
        for ($i=0;$i<count($money);$i++){
            $but =new UXCheckbox();
            $but->text=$this->Rub[$i];
            $this->add($but);
            $but->selected=true;
            $but->on('click',$f);
            
            $but->position=array($X,$Y);
            $Y+=17;
            
            
            $start='<tr class="quote__head"><th class="quote__date">Дата</th><th class="quote__value">Курс</th><th class="quote__change">Изменение</th></tr>';
            $all=file_get_contents($money[$i]);
            //echo $all."\n\n\n".
            $ind=strpos($all,$start);
            $datas=array();
            $values=array();
            $endDateStart=$this->database->query('select data from EndDate where id='.$i.' and isSTart=1;');
            foreach ($endDateStart as $record) {
                $endDateStart=$record->toArray()['data'];
                break;
            }
            $endDateEnd=$this->database->query('select data from EndDate where id='.$i.' and isSTart=0;');
            foreach ($endDateEnd as $record) {
                $endDateEnd=$record->toArray()['data'];
                break;
            }
            while($ind!=false){
                $del='<td class="quote__date">';
                $tmp=strpos($all,$del,$ind);
                $ind=$tmp;
                //echo $tmp."---\n";
                if ($tmp==false){
                    $ind=false;
                    continue;
                }
                $strdate=substr($all,$tmp);
                //echo $strdate."\n";
                $date=substr($strdate,strlen($del),20);
                $date=substr($date,0, strpos($date, '<'));
                $date=explode(".",$date);
                $date=$date[2].$date[1].$date[0];
                if (($date>=$endDateStart)&&($date<=$endDateEnd)){
                    $ind=$tmp+20;
                    continue;
                }
                array_push($datas,$date);
                $del='<span class="quote__sgn"></span>';
                $tmp=strpos($all,$del,$ind);
                $strdate=substr($all,$tmp);;
                $value=substr($strdate,strlen($del),30);
                $value=substr($value,0, strpos($value, '<'));
                $value=str_replace(',','.',$value);
                array_push($values,$value);
               $ind=$tmp+10;
            }
            for ($j=count($datas)-1;$j>=0;$j--){
               $this->database->query(
                    'insert into Kource (id, data,value) values ('.$i.', '.$datas[$j].', '.$values[$j].');'
                )->update();
            }
            if (count($datas)>0){
                 $this->database->query(
                 'update EndDate set data='.$datas[0].' where id='.$i.' and isSTart=0;'
                    //'insert into Kource (id, data,value) values ('.$i.', '."'".$datas[$j]."'".', '.$values[$j].');'
                )->update();
            }
        }
    }
    function UpdateKurse(){
        $this->textAreaAlt->text='';
        for ($i=0;$i<count($this->ids);$i++){
            $res=$this->database->query('select value from Kource where id = '.$this->ids[$i].';');
            $pos=0;
             foreach ($res as $record) {
                $tmp=$record->toArray();
                $pos=$tmp['value'];        
            }
            $this->textAreaAlt->text.=$this->Rub[$this->ids[$i]]." = ".$pos."\n";
        }
    }

    /**
     * @event construct 
     */
    function doConstruct(UXEvent $event = null)
    {  
        $isset=true;
        $ids=$this->ids;
        $this->ALL=$ids;
        try{
            $endDateEnd=$this->database->query('select * from EndDate;');
        } catch (Exception $e){
            $isset=false;
        }
        if (!$isset){
        $this->database->query(
            'create table if not exists Kource (id integer, data int, value numeric(10,4));'
        )->update();
        $this->database->query(
            'create table if not exists EndDate (id integer, isSTart bool, data integer);'
        )->update();
           for($i=0;$i<count($ids);$i++){
                $this->database->query(
                    'insert into EndDate (id, isSTart,data) values ('.$i.', '.'1, '."'".'0000'."'".');'
                )->update();
                $this->database->query(
                    'insert into EndDate (id, isSTart,data) values ('.$i.', '.'0, '."'".'0000'."'".');'
                )->update();
            }
        }
        $this->UpdateMoney();
        $this->UpdateKurse();
        
    }
    function g($matrix){
        $a = $matrix;
        $e = array();
        $count = count($a);
        for($i=0;$i<$count;$i++)
            for($j=0;$j<$count;$j++)
                $e[$i][$j]=($i==$j)? 1 : 0;
                
        for($i=0;$i<$count;$i++){
            $tmp = $a[$i][$i];
            for($j=$count-1;$j>=0;$j--){
                $e[$i][$j]/=$tmp;
                $a[$i][$j]/=$tmp;
            }
            
            for($j=0;$j<$count;$j++){
                if($j!=$i){
                    $tmp = $a[$j][$i];
                    for($k=$count-1;$k>=0;$k--){
                        $e[$j][$k]-=$e[$i][$k]*$tmp;
                        $a[$j][$k]-=$a[$i][$k]*$tmp;
                    }
                }
            }
        }
        
        for($i=0;$i<$count;$i++)
         for($j=0;$j<$count;$j++)
           $a[$i][$j]=$e[$i][$j];
           
           
           return $a;
    }
    function PrintMatr($a){
        for($i=0;$i<count($a);$i++){
             for($j=0;$j<count($a[$i]);$j++)
               echo $a[$i][$j]." ";
              echo "\n";
              } 
    }

    /**
     * @event button.click 
     */
    function doButtonClick(UXMouseEvent $event = null)
    {    
   /* $tmpa=array();
    for ($i=0;$i<3;$i++){
        array_push($tmpa,array(3));
    }
    
    $tmpa[0][0]=1;
    $tmpa[0][1]=2;
    $tmpa[0][2]=3;
    $tmpa[1][0]=0;
    $tmpa[1][1]=4;
    $tmpa[1][2]=6;
    $tmpa[2][0]=7;
    $tmpa[2][1]=8;
    $tmpa[2][2]=8;
    $this->PrintMatr($tmpa);
    echo "\n\n";
    $g=$this->g($tmpa);
    $this->PrintMatr($g);
    return;*/
    
        $x0=$this->edit->text;
        if ($x0==''){
            return;
        }
        $A=array();
        $R=array();
        for ($i=0;$i<count($this->ids);$i++){
            array_push($R,array());
            $res=$this->database->query('select value from Kource where id = '.$this->ids[$i].';');
            $prev=0;
            $avg=0;
             foreach ($res as $record) {
                $tmp=$record->toArray();
                array_push($R[$i],$tmp['value']);
                if ($prev==0){
                    $prev=$tmp['value'];
                    continue;
                }
                $val=$tmp['value'];
                $avg=($avg+($val-$prev)/$prev)/2;
                $prev=$val;
                //echo $val."-".$avg."\n";
            }
            array_push($A,$avg);
        }
        //print_r($A);
        $C=array();
        for ($i=0;$i<count($this->ids);$i++){
            array_push($C,array());
            for ($j=0;$j<count($this->ids);$j++){
                $sum=0;
                for ($k=0;$k<count($R[$i]);$k++){
                    $sum+=$R[$i][$k]*$R[$j][$k];
                }
                $sum=$sum/count($R[$i]);
                array_push($C[$i],$sum-($A[$i]*$A[$j]));
            }
        }
        $C=$this->g($C);
        $NC=array();
        for ($i=0;$i<count($C);$i++){
            for ($j=0;$j<count($C[$i]);$j++){
                $NC[$i]+=$C[$i][$j];
            }
        }
        $sum=0;
        for ($i=0;$i<count($NC);$i++){
            $sum+=$NC[$i];
        }
        for ($i=0;$i<count($NC);$i++){
            $NC[$i]=$NC[$i]/$sum;
        }
              
        $Y=array();
        $this->textArea->text='';
        for ($i=0;$i<count($this->ids);$i++){
            $Y[$i]=$NC[$i]*$x0/$R[$i][count($R[$i])-1];
            $this->textArea->text.=$this->Rub[$this->ids[$i]].' = '.$Y[$i]." (".$Y[$i]*$R[$i][count($R[$i])-1]." )\n";
        }
        
    }
}    
class Thre extends Thread{
    private $form;
    public function __construct(){
        //$this->form=&$Form;
    }
    public function run(){
        //$this->form->UpdateMoney();
    }
        
}


