<?php
require_once WEBAPP_DIR.'/modules/repository/files/fpdf/japanese.php';

class PDF_MC_Table extends PDF_Japanese
{
var $widths;
var $aligns;
var $rowFillColor;

function SetWidths($w)
{
	//Set the array of column widths
	$this->widths=$w;
}

function SetAligns($a)
{
	//Set the array of column alignments
	$this->aligns=$a;
}

function SetRowFillColor($rgb)
{
    //Set the array of column fill color
    $this->rowFillColor=$rgb;
}

function Row($data, $maxNb=20)
{
	//Calculate the height of the row
	$nb=0;
	for($i=0;$i<count($data);$i++)
	{
		if($this->CurrentFont['type']=='Type0')
		    if($this->CurrentFont['CMap']=='UniJIS-UTF16-H')
			 $nb=max($nb,$this->UniJISNbLines($this->widths[$i],$data[$i], $maxNb));
			else
			 $nb=max($nb,$this->SJISNbLines($this->widths[$i],$data[$i], $maxNb));
		else
			$nb=max($nb,$this->NbLines($this->widths[$i],$data[$i], $maxNb));
	}
    $h=($this->FontSize+$this->cMargin)*$nb;
	//Issue a page break first if needed
	$this->CheckPageBreak($h);
	//Draw the cells of the row
	for($i=0;$i<count($data);$i++)
	{
		$w=$this->widths[$i];
		$a=isset($this->aligns[$i]) ? $this->aligns[$i] : 'L';
		//Save the current position
		$x=$this->GetX();
		$y=$this->GetY();
		//Draw the border
		$this->SetFillColor($this->rowFillColor[$i][0], $this->rowFillColor[$i][1], $this->rowFillColor[$i][2]);
		$this->Rect($x,$y,$w,$h,'FD');
		//Print the text
		$this->MultiCell($w,$this->FontSize+$this->cMargin,$data[$i],0,$a,false,$maxNb);
		//Put the position to the right of the cell
		$this->SetXY($x+$w,$y);
	}
	//Go to the next line
	$this->Ln($h);
}

function CheckPageBreak($h)
{
	//If the height h would cause an overflow, add a new page immediately
	if($this->GetY()+$h>$this->PageBreakTrigger)
		$this->AddPage($this->CurOrientation);
}

function NbLines($w,$txt,$maxNl=0)
{
	//Computes the number of lines a MultiCell of width w will take
	$cw=&$this->CurrentFont['cw'];
	if($w==0)
		$w=$this->w-$this->rMargin-$this->x;
	$wmax=($w-2*$this->cMargin)*1000/$this->FontSize;
	$s=str_replace("\r",'',$txt);
	$nb=strlen($s);
	if($nb>0 and $s[$nb-1]=="\n")
		$nb--;
	$sep=-1;
	$i=0;
	$j=0;
	$l=0;
	$nl=1;
	while($i<$nb)
	{
		$c=$s[$i];
		if($c=="\n")
		{
			$i++;
			$sep=-1;
			$j=$i;
			$l=0;
			$nl++;
            if($maxNl > 0 && $nl >= $maxNl)
            {
                break;
            }
			continue;
		}
		if($c==' ')
			$sep=$i;
		$l+=$cw[$c];
		if($l>$wmax)
		{
			if($sep==-1)
			{
				if($i==$j)
					$i++;
			}
			else
				$i=$sep+1;
			$sep=-1;
			$j=$i;
			$l=0;
			$nl++;
            if($maxNl > 0 && $nl >= $maxNl)
                break;
		}
		else
			$i++;
	}
	return $nl;
}

function SJISNbLines($w,$txt,$maxNl=0)
{
	//Computes the number of lines a MultiCell of width w will take
	// SJIS version
	$cw=&$this->CurrentFont['cw'];
    if($w==0)
        $w=$this->w-$this->rMargin-$this->x;
	$wmax=($w-2*$this->cMargin)*1000/$this->FontSize;
	$s=str_replace("\r",'',$txt);
	$nb=strlen($s);
	if($nb>0 and $s[$nb-1]=="\n")
	$nb--;
	$sep=-1;
	$i=0;
	$j=0;
	$l=0;
	$nl=1;
	while($i<$nb)
	{
        // Add A.Suzuki
        if($s[$i] == "\n")
        {
            $nl++;
            $i+=1;
            $sep=-1;
            $j=$i;
            $l=0;
            if($maxNl > 0 && $nl >= $maxNl)
                break;
        }
	
		// Get next character
		$c=$s[$i];
		$o=ord($c);
		if($o==10)
		{
			// Explicit line break
			$i++;
			$sep=-1;
			$j=$i;
			$l=0;
			//if($nl==1)
			//{
			//	// Go to left margin
			//	$this->x=$this->lMargin;
			//	$w=$this->w-$this->rMargin-$this->x;
			//	$wmax=($w-2*$this->cMargin)*1000/$this->FontSize;
			//}
			$nl++;
            if($maxNl > 0 && $nl >= $maxNl)
                break;
			continue;
		}
		if($o<128)
		{
			// ASCII
			$l+=$cw[$c];
			$n=1;
			if($o==32)
				$sep=$i;
		}
		elseif($o>=161 && $o<=223)
		{
			// Half-width katakana
			$l+=500;
			$n=1;
			$sep=$i;
		}
		else
		{
			// Full-width character
			$l+=1000;
			$n=2;
			$sep=$i;
		}
		if($l>$wmax)
		{
			// Automatic line break
			if($sep==-1 || $i==$j)
			{
				if($this->x>$this->lMargin)
				{
					// Move to next line
					$this->x=$this->lMargin;
					$this->y+=$h;
					$w=$this->w-$this->rMargin-$this->x;
					$wmax=($w-2*$this->cMargin)*1000/$this->FontSize;
					$i+=$n;
					$nl++;
                    if($maxNl > 0 && $nl >= $maxNl)
                        break;
					continue;
				}
			}
			else
			{
				$i=($s[$sep]==' ') ? $sep+1 : $sep;
			}
			$sep=-1;
			$j=$i;
			$l=0;
			//if($nl==1)
			//{
			//	$this->x=$this->lMargin;
			//	$w=$this->w-$this->rMargin-$this->x;
			//	$wmax=($w-2*$this->cMargin)*1000/$this->FontSize;
			//}
			$nl++;
            if($maxNl > 0 && $nl >= $maxNl)
                break;
		}
		else
		{
			$i+=$n;
			if($o>=128)
				$sep=$i;
		}
	}
	return $nl;
}

function UniJISNbLines($w,$txt,$maxNl=0)
{
    //Computes the number of lines a MultiCell of width w will take
    // SJIS version
    $cw=&$this->CurrentFont['cw'];
    if($w==0)
        $w=$this->w-$this->rMargin-$this->x;
    $wmax=($w-2*$this->cMargin)*1000/$this->FontSize;
    //$s=str_replace("\r",'',$txt);
    $s=$txt;
    $nb=strlen($s);
    if($nb>0 and $s[$nb-1]=="\n")
    $nb--;
    $sep=-1;
    $i=0;
    $j=0;
    $l=0;
    $nl=1;
    while($i<$nb)
    {
        // Add A.Suzuki
        if($s[$i] == "\n")
        {
            $nl++;
            $i+=1;
            $sep=-1;
            $j=$i;
            $l=0;
            if($maxNl > 0 && $nl >= $maxNl)
                break;
        }
    
        // Get next character
        $c=$s[$i];
        $o=ord($c);
        if($o==10)
        {
            // Explicit line break
            $i++;
            $sep=-1;
            $j=$i;
            $l=0;
            //if($nl==1)
            //{
            //  // Go to left margin
            //  $this->x=$this->lMargin;
            //  $w=$this->w-$this->rMargin-$this->x;
            //  $wmax=($w-2*$this->cMargin)*1000/$this->FontSize;
            //}
            $nl++;
            if($maxNl > 0 && $nl >= $maxNl)
                break;
            continue;
        }
        if($o<128)
        {
            // ASCII
            $l+=$cw[$c];
            $n=1;
            if($o==32)
                $sep=$i;
        }
        elseif($o>=161 && $o<=223)
        {
            // Half-width katakana
            $l+=500;
            $n=1;
            $sep=$i;
        }
        else
        {
            // Full-width character
            $l+=1000;
            $n=2;
            $sep=$i;
        }
        if($l>$wmax)
        {
            // Automatic line break
            if($sep==-1 || $i==$j)
            {
                if($this->x>$this->lMargin)
                {
                    // Move to next line
                    $this->x=$this->lMargin;
                    $this->y+=$h;
                    $w=$this->w-$this->rMargin-$this->x;
                    $wmax=($w-2*$this->cMargin)*1000/$this->FontSize;
                    $i+=$n;
                    $nl++;
                    if($maxNl > 0 && $nl >= $maxNl)
                        break;
                    continue;
                }
            }
            else
            {
                if($i%2==1)
                    $i--;
                    $sep--;
                $i=($s[$sep]==' ') ? $sep+1 : $sep;
            }
            $sep=-1;
            $j=$i;
            $l=0;
            //if($nl==1)
            //{
            //  $this->x=$this->lMargin;
            //  $w=$this->w-$this->rMargin-$this->x;
            //  $wmax=($w-2*$this->cMargin)*1000/$this->FontSize;
            //}
            $nl++;
            if($maxNl > 0 && $nl >= $maxNl)
                break;
        }
        else
        {
            $i+=$n;
            if($o>=128)
                $sep=$i;
        }
    }
    return $nl;
}
}
?>
