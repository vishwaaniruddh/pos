<?php
ob_start();

ini_set('display_errors', 0); // Disable display errors on PDF generation stream
error_reporting(E_ALL & ~E_DEPRECATED & ~E_NOTICE & ~E_WARNING);

ini_set("memory_limit", "-1");
set_time_limit(0);

if (file_exists('../db_connection.php')) {
    include_once('../db_connection.php');
}

if (file_exists('fpdf.php')) {
    include_once('fpdf.php');
}
if (file_exists('html2pdf.php')) {
    include_once('html2pdf.php');
}

class PDF extends FPDF
{
    // Variables of html parser
    public $B;
    public $I;
    public $U;
    public $HREF;
    public $fontList;
    public $fontlist;
    public $issetfont;
    public $issetcolor;

    public $tableborder;
    public $tdbegin;
    public $tdwidth;
    public $tdheight;
    public $tdalign;
    public $tdbgcolor;

    public $oldx;
    public $oldy;
    public $cMargin;

    function __construct($orientation = 'P', $unit = 'mm', $format = 'A4')
    {
        //Call parent constructor
        parent::__construct($orientation, $unit, $format);

        //Initialization
        $this->B = 0;
        $this->I = 0;
        $this->U = 0;
        $this->HREF = '';

        $this->tableborder = 0;
        $this->tdbegin = false;
        $this->tdwidth = 0;
        $this->tdheight = 0;
        $this->tdalign = "L";
        $this->tdbgcolor = false;

        $this->oldx = 0;
        $this->oldy = 0;

        $this->fontlist = array("arial", "times", "courier", "helvetica", "symbol");
        $this->issetfont = false;
        $this->issetcolor = false;
    }

    //////////////////////////////////////
//html parser

    function WriteHTML($html)
    {
        $html = strip_tags($html, "<b><u><i><a><img><p><br><strong><em><font><tr><blockquote><hr><td><tr><table><sup>"); //remove all unsupported tags
        $html = str_replace("\n", '', $html); //replace carriage returns with spaces
        $html = str_replace("\t", '', $html); //replace carriage returns with spaces
        $a = preg_split('/<(.*)>/U', $html, -1, PREG_SPLIT_DELIM_CAPTURE); //explode the string
        foreach ($a as $i => $e) {
            if ($i % 2 == 0) {
                //Text
                if ($this->HREF)
                    $this->PutLink($this->HREF, $e);
                elseif ($this->tdbegin) {
                    if (trim($e) != '' && $e != "&nbsp;") {
                        $this->Cell($this->tdwidth, $this->tdheight, $e, $this->tableborder, '', $this->tdalign, $this->tdbgcolor);
                    } elseif ($e == "&nbsp;") {
                        $this->Cell($this->tdwidth, $this->tdheight, '', $this->tableborder, '', $this->tdalign, $this->tdbgcolor);
                    }
                } else
                    $this->Write(5, stripslashes(txtentities($e)));
            } else {
                //Tag
                if ($e[0] == '/')
                    $this->CloseTag(strtoupper(substr($e, 1)));
                else {
                    //Extract attributes
                    $a2 = explode(' ', $e);
                    $tag = strtoupper(array_shift($a2));
                    $attr = array();
                    foreach ($a2 as $v) {
                        if (preg_match('/([^=]*)=["\']?([^"\']*)/', $v, $a3))
                            $attr[strtoupper($a3[1])] = $a3[2];
                    }
                    $this->OpenTag($tag, $attr);
                }
            }
        }
    }

    function OpenTag($tag, $attr)
    {
        //Opening tag
        switch ($tag) {

            case 'SUP':
                if (!empty($attr['SUP'])) {
                    //Set current font to 6pt     
                    $this->SetFont('', '', 6);
                    //Start 125cm plus width of cell to the right of left margin         
                    //Superscript "1" 
                    $this->Cell(2, 2, $attr['SUP'], 0, 0, 'L');
                }
                break;

            case 'TABLE': // TABLE-BEGIN
                if (!empty($attr['BORDER']))
                    $this->tableborder = $attr['BORDER'];
                else
                    $this->tableborder = 0;
                break;
            case 'TR': //TR-BEGIN
                break;
            case 'TD': // TD-BEGIN
                if (!empty($attr['WIDTH']))
                    $this->tdwidth = ($attr['WIDTH'] / 4);
                else
                    $this->tdwidth = 40; // Set to your own width if you need bigger fixed cells
                if (!empty($attr['HEIGHT']))
                    $this->tdheight = ($attr['HEIGHT'] / 6);
                else
                    $this->tdheight = 6; // Set to your own height if you need bigger fixed cells
                if (!empty($attr['ALIGN'])) {
                    $align = $attr['ALIGN'];
                    if ($align == 'LEFT')
                        $this->tdalign = 'L';
                    if ($align == 'CENTER')
                        $this->tdalign = 'C';
                    if ($align == 'RIGHT')
                        $this->tdalign = 'R';
                } else
                    $this->tdalign = 'L'; // Set to your own
                if (!empty($attr['BGCOLOR'])) {
                    $coul = hex2dec($attr['BGCOLOR']);
                    $this->SetFillColor($coul['R'], $coul['G'], $coul['B']);
                    $this->tdbgcolor = true;
                }
                $this->tdbegin = true;
                break;

            case 'HR':
                if (!empty($attr['WIDTH']))
                    $Width = $attr['WIDTH'];
                else
                    $Width = $this->w - $this->lMargin - $this->rMargin;
                $x = $this->GetX();
                $y = $this->GetY();
                $this->SetLineWidth(0.2);
                $this->Line($x, $y, $x + $Width, $y);
                $this->SetLineWidth(0.2);
                $this->Ln(1);
                break;
            case 'STRONG':
                $this->SetStyle('B', true);
                break;
            case 'EM':
                $this->SetStyle('I', true);
                break;
            case 'B':
            case 'I':
            case 'U':
                $this->SetStyle($tag, true);
                break;
            case 'A':
                $this->HREF = $attr['HREF'];
                break;
            case 'IMG':
                if (isset($attr['SRC']) && (isset($attr['WIDTH']) || isset($attr['HEIGHT']))) {
                    if (!isset($attr['WIDTH']))
                        $attr['WIDTH'] = 0;
                    if (!isset($attr['HEIGHT']))
                        $attr['HEIGHT'] = 0;
                    $this->Image($attr['SRC'], $this->GetX(), $this->GetY(), px2mm($attr['WIDTH']), px2mm($attr['HEIGHT']));
                }
                break;
            case 'BLOCKQUOTE':
            case 'BR':
                $this->Ln(5);
                break;
            case 'P':
                $this->Ln(10);
                break;
            case 'FONT':
                if (isset($attr['COLOR']) && $attr['COLOR'] != '') {
                    $coul = hex2dec($attr['COLOR']);
                    $this->SetTextColor($coul['R'], $coul['G'], $coul['B']);
                    $this->issetcolor = true;
                }
                if (isset($attr['FACE']) && in_array(strtolower($attr['FACE']), $this->fontlist)) {
                    $this->SetFont(strtolower($attr['FACE']));
                    $this->issetfont = true;
                }
                if (isset($attr['FACE']) && in_array(strtolower($attr['FACE']), $this->fontlist) && isset($attr['SIZE']) && $attr['SIZE'] != '') {
                    $this->SetFont(strtolower($attr['FACE']), '', $attr['SIZE']);
                    $this->issetfont = true;
                }
                break;
        }
    }

    function CloseTag($tag)
    {
        //Closing tag
        if ($tag == 'SUP') {
        }

        if ($tag == 'TD') { // TD-END
            $this->tdbegin = false;
            $this->tdwidth = 0;
            $this->tdheight = 0;
            $this->tdalign = "L";
            $this->tdbgcolor = false;
        }
        if ($tag == 'TR') { // TR-END
            $this->Ln();
        }
        if ($tag == 'TABLE') { // TABLE-END
            $this->tableborder = 0;
        }

        if ($tag == 'STRONG')
            $tag = 'B';
        if ($tag == 'EM')
            $tag = 'I';
        if ($tag == 'B' || $tag == 'I' || $tag == 'U')
            $this->SetStyle($tag, false);
        if ($tag == 'A')
            $this->HREF = '';
        if ($tag == 'FONT') {
            if ($this->issetcolor == true) {
                $this->SetTextColor(0);
            }
            if ($this->issetfont) {
                $this->SetFont('arial');
                $this->issetfont = false;
            }
        }
    }

    function SetStyle($tag, $enable)
    {
        //Modify style and select corresponding font
        $this->$tag += ($enable ? 1 : -1);
        $style = '';
        foreach (array('B', 'I', 'U') as $s) {
            if ($this->$s > 0)
                $style .= $s;
        }
        $this->SetFont('', $style);
    }

    function PutLink($URL, $txt)
    {
        //Put a hyperlink
        $this->SetTextColor(0, 0, 255);
        $this->SetStyle('U', true);
        $this->Write(5, $txt, $URL);
        $this->SetStyle('U', false);
        $this->SetTextColor(0);
    }

    function SetCellMargin($margin)
    {
        // Set cell margin
        $this->cMargin = $margin;
    }


}



// Create a new PDF with landscape orientation and custom size
$pdf = new PDF('L', 'in', array(7.50, 10.00));
$pdf->SetAutoPageBreak(false); // Disable automatic page breaks

// Title Page
$pdf->AddPage();
$pdf->SetFont('Arial', 'B', 48); // Bold, large font
$pdf->SetTextColor(75, 73, 172); // Instagram-like color
$pdf->SetXY(0, 3); // Center the title vertically
$pdf->Cell(10.00, 1, 'Sri Shringarr Fashion Studio', 0, 1, 'C'); // Center the title horizontally

// Add a smaller subtitle or logo-like text
$pdf->SetFont('Arial', 'I', 24); // Italic, medium font
$pdf->SetTextColor(150, 150, 255); // A lighter, complementary color
$pdf->SetXY(0, 4); // Position slightly below the main title
$pdf->Cell(10.00, 1, 'The Ultimate Fashion Destination', 0, 1, 'C'); // Centered subtitle

// Add an additional decorative line or design element (optional)
$pdf->SetLineWidth(0.1);
$pdf->SetDrawColor(75, 73, 172); // Match the title color
$pdf->Line(2, 4.8, 8, 4.8); // Draw a line below the title

// Add a new page for the data content
$pdf->AddPage();
$pdf->SetFont('Arial', 'B', 20); // Set font for the data content
$pdf->SetTextColor(0, 0, 0); // Reset text color to black

// Margins and layout settings for the data pages
$left_margin = 0.5;
$right_margin = 0.5;
$top_margin = 0.5;
$bottom_margin = 0.5;

$column_width = 4.4; // Width of each image
$column_spacing = 0.2; // Space between columns
$columns = 2;

// Calculate usable height
$usable_height = 10.00 - $top_margin - $bottom_margin;

// Calculate X positions for columns
$positions = [];
for ($col = 0; $col < $columns; $col++) {
    $positions[$col] = $left_margin + ($col * ($column_width + $column_spacing));
}

// Initialize Y position
$current_y = $top_margin;

// Initialize counters
$i = 0;

function slugify_product_title($text) {
    $text = preg_replace('~[^\pL\d]+~u', '-', $text);
    $text = @iconv('utf-8', 'us-ascii//TRANSLIT', $text);
    $text = preg_replace('~[^-\w]+~', '', $text);
    $text = trim($text, '-');
    $text = preg_replace('~-+~', '-', $text);
    $text = strtolower($text);
    return empty($text) ? 'product' : $text;
}

// Prepare POST data
$pdfName = isset($_REQUEST['pdfName']) ? $_REQUEST['pdfName'] : 'output';

if (!isset($web_con) || !$web_con) {
    $dbhost = "localhost";
    $dbuser = "u464193275_srishrinjuser";
    $dbpass = "9b@hMgk!=zI";
    $db = "u464193275_srishrinjewels";
    
    $web_con = @new mysqli($dbhost, $dbuser, $dbpass, $db);
    if (!$web_con || $web_con->connect_error) {
        $is_local = isset($_SERVER['HTTP_HOST']) && (in_array($_SERVER['HTTP_HOST'], ['localhost', '127.0.0.1', '::1']) || strpos($_SERVER['HTTP_HOST'], 'localhost:') === 0);
        if ($is_local) {
            $web_con = @mysqli_connect("localhost", "root", "", "u464193275_srishringarr");
        } else {
            $web_con = @mysqli_connect("localhost", "u464193275_sarmicropos", "Mypos1234", "u464193275_srishringarr");
        }
    }
}

// Filter POST data to exclude submit button and pdfName
$product_posts = array();
foreach ($_POST as $k => $v) {
    if ($k === 'pdfName' || $k === 'submit') continue;
    $product_posts[$k] = $v;
}

$total_items = count($product_posts);

// Loop through each product
foreach ($product_posts as $radioName => $selectedValue) {
    // Extract product details
    $nameParts = explode('-', $radioName);
    if (count($nameParts) > 1) {
        $product_id = array_pop($nameParts);
        $sku = implode('-', $nameParts);
    } else {
        $sku = $radioName;
        $product_id = $radioName;
    }

    $selectedImagePart = explode('-', $selectedValue);
    $selectedImage = end($selectedImagePart);

    // Fetch product name for SEO slug URL
    $product_name = '';
    if ($web_con) {
        // Query garments
        $resName = @mysqli_query($web_con, "SELECT name FROM `garments` WHERE `garment_id`='" . mysqli_real_escape_string($web_con, $product_id) . "' LIMIT 1");
        if ($resName && $rowN = @mysqli_fetch_assoc($resName)) {
            $product_name = trim($rowN['name']);
        }

        // Query product
        if (empty($product_name)) {
            $resName = @mysqli_query($web_con, "SELECT product_name FROM `product` WHERE `product_id`='" . mysqli_real_escape_string($web_con, $product_id) . "' LIMIT 1");
            if ($resName && $rowN = @mysqli_fetch_assoc($resName)) {
                $product_name = trim($rowN['product_name']);
            }
        }

        // Query phppos_items
        if (empty($product_name)) {
            $resName = @mysqli_query($web_con, "SELECT name FROM `phppos_items` WHERE `item_number`='" . mysqli_real_escape_string($web_con, $sku) . "' LIMIT 1");
            if ($resName && $rowN = @mysqli_fetch_assoc($resName)) {
                $product_name = trim($rowN['name']);
            }
        }
    }

    if (empty($product_name)) {
        $product_name = $sku;
    }

    $product_slug = slugify_product_title($product_name);
    $product_url = 'https://srishringarr.com/product/' . $product_slug . '-' . $product_id;

    // Database query to get image
    $sqlimg = "SELECT img_name FROM `product_images_new` WHERE `gproduct_id`='$product_id'";
    if (!empty($selectedImage)) {
        $sqlimg .= " AND img_name LIKE '%" . mysqli_real_escape_string($web_con, $selectedImage) . "%'";
    }

    $qryimg = mysqli_query($web_con, $sqlimg);

    if (!$qryimg || mysqli_num_rows($qryimg) == 0) {
        $sqlimg = "SELECT img_name FROM `product_images_new` WHERE `product_id`='$product_id'";
        if (!empty($selectedImage)) {
            $sqlimg .= " AND img_name LIKE '%" . mysqli_real_escape_string($web_con, $selectedImage) . "%'";
        }

        $qryimg = mysqli_query($web_con, $sqlimg);
    }

    if ($qryimg && mysqli_num_rows($qryimg) > 0) {
        $rowimg = mysqli_fetch_array($qryimg);
        $raw_img_name = ltrim(trim(str_replace("/ ", "/", $rowimg['img_name'])), '/');
        if (strpos($raw_img_name, 'uploads/') === 0) {
            $source_img = "yn/" . $raw_img_name;
        } else {
            $source_img = "yn/uploads/" . $raw_img_name;
        }
        
        $_file_parent = "https://srishringarr.com/";
        $_new_filename = $_file_parent . $source_img;

        // Check if the image exists on the server
        if (!@file_get_contents($_new_filename)) { // Suppress warnings with @
            $destination_img = "../../" . $source_img;
        } else {
            $destination_img = $_new_filename; // Use the URL if exists
        }
    } else {
        // Default image if none found
        $destination_img = 'https://srishringarr.com/yn/uploads/no-image.jpg';
    }

    // Determine column and row
    $column = $i % $columns;
    $row = floor($i / $columns);

    // Calculate X and Y positions
    $x = $positions[$column];
    $y = $current_y;

    // Maximum bounding dimensions per image box
    $max_width = $column_width; // 4.4 inches
    $max_height = 5.5;          // 5.5 inches max height

    // Calculate proportional rendering dimensions to preserve aspect ratio (prevent stretch/compress)
    $render_w = $max_width;
    $render_h = 0; // Passing 0 to FPDF automatically calculates proportional height

    $img_info = @getimagesize($destination_img);
    if ($img_info && $img_info[0] > 0 && $img_info[1] > 0) {
        $orig_w = $img_info[0];
        $orig_h = $img_info[1];

        $scale = min($max_width / $orig_w, $max_height / $orig_h);
        $render_w = $orig_w * $scale;
        $render_h = $orig_h * $scale;
    }

    // Center image horizontally inside column box
    $img_x = $x + ($max_width - $render_w) / 2;
    $img_y = $y;

    $box_height = ($render_h > 0) ? $render_h : $max_height;

    // Check if adding the image exceeds the usable page height
    if ($y + $box_height + 0.6 > ($top_margin + $usable_height)) {
        $pdf->AddPage();
        $current_y = $top_margin;
        $y = $current_y;
        $img_y = $y;
    }

    // Add Image preserving exact natural aspect ratio
    $pdf->Image($destination_img, $img_x, $img_y, $render_w, $render_h);

    // Add hyperlink over image using SEO Product URL
    $pdf->Link($img_x, $img_y, $render_w, $box_height, $product_url);

    // Add SKU text centered below the image with hyperlink
    $pdf->SetTextColor(75, 73, 172);
    $pdf->SetXY($x, $img_y + $box_height + 0.15);
    $pdf->SetFont('Arial', 'B', 12);
    $pdf->Cell($column_width, 0.3, 'View: ' . $sku, 0, 0, 'C', false, $product_url);

    // Increment counters
    $i++;

    // After two columns, move to next row
    if ($column == ($columns - 1)) {
        $current_y += $box_height + 0.6;
    }

    // If it's the last item and it's not filling the entire row, adjust Y
    if ($i == $total_items && $column != ($columns - 1)) {
        $current_y += $box_height + 0.6;
    }
}

// Output the PDF - Clean any previous buffer to prevent header output errors
if (ob_get_length()) {
    ob_end_clean();
}

$pdf->Output($pdfName . '.pdf', 'D'); // 'I' for inline display, 'D' for download

?>