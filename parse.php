<?php
/*_____________________________

    1. uloha do predmetu IPP
    Autor: Michal Belovec 
    Login: xbelov04
_______________________________*/
ini_set('display_errors', 'stderr');

function isLabel($str)
{
    return(!preg_match("/^(GF|LF|TF|bool|int|nil|string)@/", $str) and
    preg_match("/^[A-Za-z_\-$&%*!?]+[A-Za-z0-9_\-$&%\/*!?]*/", $str));
}

function isType($str)
{
    return(preg_match("/^(bool|int|string)$/", $str));
}

function isVar($str)
{
    return(preg_match("/^(GF|LF|TF)@[A-Za-z_\-$&*!?%]+[A-Za-z0-9_\-$&\/*!?%]*/", $str));
}

function isInt($str)
{
    return(preg_match("/^(int@)[+\-]{0,1}[0-9]+/", $str));
}

function isBool($str)
{
    return(preg_match("/^(bool@)(true|false)$/", $str));
}

function isNil($str)
{
    return(preg_match("/^(nil@nil)$/", $str));
}

function isString($str)
{
    return(preg_match("/^string@/", $str)); //nie je to string
}

function getString($str)
{
    $value = preg_replace('/^string@/', '', $str);
    $valuesOk = true;
    for ($char = 0; $char < strlen($str); $char++){
        if($str[$char] == '\\')
        {
            $valuesOk = false;
            $char++;
            if(is_numeric($str[$char]) and is_numeric($str[$char+1]) and is_numeric($str[$char+2])){
                $char = $char+3;
                $valuesOk = true;
            }
        }
        if(!$valuesOk) exit(23); //string nie je validny
    }
    $value = preg_replace('/&/', '&amp;', $value);
    $value = preg_replace('/</', '&lt;', $value);
    $value = preg_replace('/>/', '&gt;', $value);
    return($value);
}

function printVar($str)
{
    if(!isVar($str)) exit(23); //nespravny argument
    $value = preg_replace('/&/', '&amp;', $str);
    $value = preg_replace('/</', '&lt;', $value);
    $value = preg_replace('/>/', '&gt;', $value);
    echo("\t\t<arg1 type=\"var\">$value</arg1>\n");
}

function printLabel($str)
{
    if(!isLabel($str)) exit(23); //nespravny argument
    echo("\t\t<arg1 type=\"label\">$str</arg1>\n");
}

function printType($str)
{
    if(!isType($str)) exit(23); //nespravny prvy argument
    echo("\t\t<arg2 type=\"type\">$str</arg2>\n");
}

function printSymb($str, $argNumber)
{
    if(isVar($str))
    {
        $value = preg_replace('/&/', '&amp;', $str);
        $value = preg_replace('/</', '&lt;', $value);
        $value = preg_replace('/>/', '&gt;', $value);
        echo("\t\t<arg$argNumber type=\"var\">$value</arg$argNumber>\n");
    }
    elseif(isBool($str))
    {
        $print = preg_replace('/^bool@/', '', $str);
        echo("\t\t<arg$argNumber type=\"bool\">$print</arg$argNumber>\n");
    }
    elseif(isInt($str))
    {
        $print = preg_replace('/^int@/', '', $str);
        echo("\t\t<arg$argNumber type=\"int\">$print</arg$argNumber>\n");
    }
    elseif(isNil($str))
    {
        $print = preg_replace('/^nil@/', '', $str);
        echo("\t\t<arg$argNumber type=\"nil\">$print</arg$argNumber>\n");
    }
    elseif(isString($str))
    {
        $print = getString($str);
        echo("\t\t<arg$argNumber type=\"string\">$print</arg$argNumber>\n");
    }
    else exit(23); //nespravny argument
}

function printUsage()
{
    echo(" USAGE:");
    echo(" for running the script:");
    echo("\tphp8.1 parse.php\n");
    echo(" or\n");
    echo(" for showing the help:");
    echo("\tphp8.1 parse.php --help\n");
}

if($argc == 1){
    $opNumber = 1;
    $missingHeader = true;
    $comment = false;
}
elseif($argc == 2 and $argv[1] == "--help"){
    printUsage();
    exit(0);
}
else{
    exit(10); //nespravny pocet argumentov alebo nespravny argument
}

while($line = fgets(STDIN))
{
    //odstrani sa znak konca riadku
    $line = trim($line, "\n");
    //ak je riadok prazdny, preskoci sa
    if($line == '') continue;
    //najprv sa odstrania komentare
    $tmp = explode('#', $line);
    if(count($tmp) > 1) $comment = true;
    $withoutComments = $tmp[0];
    //potom sa odstrania viacere medzery za sebou
    $withoutSpaces = preg_replace('/\s+/', ' ', $withoutComments);
    //nakoniec sa rozdelia riadky po
    $words = explode(' ', $withoutSpaces);
    
    if($missingHeader)
    {
        if(count($words) >= 1 and preg_match('/^((\s)*.IPPcode23)$/', $words[0]))
        {
            $missingHeader = false;
            echo("<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n");
            echo("<program language=\"IPPcode23\">\n");
            $comment = false;
            continue;
        }
        elseif(count($words) == 1 and $words[0] == '') continue; //je tam iba koment, hlavicka moze byt na dalsom riadku
        elseif(count($words) > 1 and $words[0] == '' and preg_match('/^((\s)*.IPPcode23)$/', $words[1]))
        {
            $missingHeader = false;
            echo("<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n");
            echo("<program language=\"IPPcode23\">\n");
            $comment = false;
            continue;
        }
        else exit(21); //chybna alebo chybajuca hlavicka
    }
    $instruction = strtoupper($words[0]);
    $len = count($words); //pocet slov v jednom riadku
    switch($instruction)
    {
        case 'BREAK':
        case 'CREATEFRAME':
        case 'POPFRAME':
        case 'PUSHFRAME':
        case 'RETURN':
            if($comment) //ak je koment, posledne slovo je prazdne, tak ho odstrani
            {
                if($words[1] == '') unset($words[1]);
                else exit(23); //nespravny pocet argumentov
            }
            elseif(($len > 1)) exit(23); //nespravny pocet argumentov
            echo("\t<instruction order=\"$opNumber\" opcode=\"$instruction\">\n");
            $opNumber++;
            $comment = false;
            echo("\t</instruction>\n");
            break;

        case 'CALL':
        case 'JUMP':
        case 'LABEL':
            if($comment) //ak je koment, posledne slovo je prazdne, tak ho odstrani
            {
                if($words[2] == '') unset($words[2]);
                else exit(23); //nespravny pocet argumentov
            }
            elseif(($len > 2)) exit(23); //nespravny pocet argumentov
            echo("\t<instruction order=\"$opNumber\" opcode=\"$instruction\">\n");
            $opNumber++;
            $comment = false;
            //prvy argument
            printLabel($words[1]);
            echo("\t</instruction>\n");
            break;

        case 'DEFVAR':
        case 'POPS':
            if($comment) //ak je koment, posledne slovo je prazdne, tak ho odstrani
            {
                if($words[2] == '') unset($words[2]);
                else exit(23); //nespravny pocet argumentov
            }
            elseif(($len > 2)) exit(23); //nespravny pocet argumentov
            echo("\t<instruction order=\"$opNumber\" opcode=\"$instruction\">\n");
            $opNumber++;
            $comment = false;
            //prvy argument
            printVar($words[1]);
            echo("\t</instruction>\n");
            break;
        
        case 'DPRINT':
        case 'EXIT':
        case 'PUSHS':
        case 'WRITE':
            if($comment) //ak je koment, posledne slovo je prazdne, tak ho odstrani
            {
                if($words[2] == '') unset($words[2]);
                else exit(23); //nespravny pocet argumentov
            }
            elseif(($len > 2)) exit(23); //nespravny pocet argumentov
            echo("\t<instruction order=\"$opNumber\" opcode=\"$instruction\">\n");
            $opNumber++;
            $comment = false;
            //prvy argument
            printSymb($words[1], 1);
            echo("\t</instruction>\n");
            break;

        case 'INT2CHAR':
        case 'MOVE':
        case 'NOT':
        case 'STRLEN':
        case 'TYPE':
            if($comment) //ak je koment, posledne slovo je prazdne, tak ho odstrani
            {
                if($words[3] == '') unset($words[3]);
                else exit(23); //nespravny pocet argumentov
            }
            elseif(($len > 3)) exit(23); //nespravny pocet argumentov
            echo("\t<instruction order=\"$opNumber\" opcode=\"$instruction\">\n");
            $opNumber++;
            $comment = false;
            //prvy argument
            printVar($words[1]);
            //druhy argument
            printSymb($words[2], 2);
            echo("\t</instruction>\n");
            break;

        case 'INT2CHAR':
        case 'MOVE':
        case 'NOT':
        case 'STRLEN':
        case 'TYPE':
            if($comment) //ak je koment, posledne slovo je prazdne, tak ho odstrani
            {
                if($words[3] == '') unset($words[3]);
                else exit(23); //nespravny pocet argumentov
            }
            elseif(($len > 3)) exit(23); //nespravny pocet argumentov
            echo("\t<instruction order=\"$opNumber\" opcode=\"$instruction\">\n");
            $opNumber++;
            $comment = false;
            //prvy argument
            printVar($words[1]);
            //druhy argument
            printSymb($words[2], 2);
            echo("\t</instruction>\n");
            break;

        case 'READ':
            if($comment) //ak je koment, posledne slovo je prazdne, tak ho odstrani
            {
                if($words[3] == '') unset($words[3]);
                else exit(23); //nespravny pocet argumentov
            }
            elseif(($len > 3)) exit(23); //nespravny pocet argumentov
            echo("\t<instruction order=\"$opNumber\" opcode=\"$instruction\">\n");
            $opNumber++;
            $comment = false;
            //prvy argument
            printVar($words[1]);
            //druhy argument
            printType($words[2]);
            echo("\t</instruction>\n");
            break;

        case 'ADD':
        case 'AND':
        case 'CONCAT':
        case 'EQ':
        case 'GETCHAR':
        case 'GT':
        case 'IDIV':
        case 'LT':
        case 'MUL':
        case 'OR':
        case 'SETCHAR':
        case 'STRI2INT':
        case 'SUB':
            if($comment) //ak je koment, posledne slovo je prazdne, tak ho odstrani
            {
                if($words[4] == '') unset($words[4]);
                else exit(23); //nespravny pocet argumentov
            }
            elseif(($len > 4)) exit(23); //nespravny pocet argumentov
            echo("\t<instruction order=\"$opNumber\" opcode=\"$instruction\">\n");
            $opNumber++;
            $comment = false;
            //prvy argument
            printVar($words[1]);
            //druhy argument
            printSymb($words[2], 2);
            //treti argument
            printSymb($words[3], 3);
            echo("\t</instruction>\n");
            break;

        case 'JUMPIFEQ':
        case 'JUMPIFNEQ':
            if($comment) //ak je koment, posledne slovo je prazdne, tak ho odstrani
            {
                if($words[4] == '') unset($words[4]);
                else exit(23); //nespravny pocet argumentov
            }
            elseif(($len > 4)) exit(23); //nespravny pocet argumentov
            echo("\t<instruction order=\"$opNumber\" opcode=\"$instruction\">\n");
            $opNumber++;
            $comment = false;
            //prvy argument
            printLabel($words[1]);
            //druhy argument
            printSymb($words[2], 2);
            //treti argument
            printSymb($words[3], 3);
            echo("\t</instruction>\n");
            break;

        case '':
            //tento case je kvoli komentarom
            break;

        default:
            exit(22);
    }
}
echo("</program>\n");
?>