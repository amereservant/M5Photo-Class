<?php
/**
 * M5Photo Class
 *
 * M5Photo class is a class designed to make photo processing/resizing simple by
 * using the PHP GD Library.  For some functions to work/be available, you must have
 * a "package" version of GD Library installed.  You can read more at 
 * {@link http://php.net} regarding the FILTER options.
 *
 * This class will automatically maintain the photo's aspect ratio, regardless of
 * the width/height settings.  It will first determine which is the larger value
 * and then resize according to that value's specified resize_to value.
 * The smaller value will automatically be calculated based on the photo's aspect
 * ratio.
 * This behavior will change in future versions to allow greater flexibility, although
 * an automatic ratio will still be setable via a parameter.
 *
 * PHP 5
 *
 * @category    Images
 * @package     M5Photos
 * @version     1.0
 * @author      David Miles <david@amereservant.com>
 * @link        http://github.com/amereservant/M5Photo-Class
 * @license     http://creativecommons.org/licenses/MIT/ MIT License
 * @TODO        Add crop method, impliment $chmod property in output files
 */
class m5Photo {

  /**
   * The Source File
   *
   * This is the file to be processed.  This property get's set by the 
   * {@link resizeImage()} method.
   *
   * @var       string
   * @access    protected
   */
   protected $source_file;
   
  /**
   * The Target File
   *
   * This property allows you to specify a different target file from the 
   * {@link $source_file} property that the new image will be created as.  
   * The target file MUST be writable.
   *
   * @var       string
   * @access    protected
   */
   protected $target_file;
   
  /**
   * Image MIME Type
   *
   * This get's set by the {@link setImageInfo()} method using the exif_imagetype() 
   * function.  It contains the images mime-type.
   * This could be used for checking allowed mime types.
   *
   * @var       string
   * @access    public
   */
   public $mime_type;
   
  /**
   * Image EXIF Data
   *
   * This get's set by the {@link setImageInfo()} method using the read_exif_data()
   * function.  This information is available as an additional resource and 
   * currently not used by this class.
   *
   * @var       string
   * @access    protected
   */
   protected $exif_data;
   
  /**
   * Source Image Width
   *
   * This get's set by the {@link resizeImage()} method via the GD function imageSX().
   * This and the {@link $source_height} are used to figure the source image's aspect
   * ratio and also to verify that the image is larger than the resized sizes if
   * {@link $resize_if_smaller} is set to 'true'.
   *
   * @var       string
   * @access    protected
   */
   protected $source_width;
   
  /**
   * Source Image Height
   *
   * This get's set by the {@link resizeImage()} method via the GD function imageSY().
   * This and the {@link $source_height} are used to figure the source image's aspect
   * ratio and also to verify that the image is larger than the resized sizes if
   * {@link $resize_if_smaller} is set to 'true'.
   *
   * @var       string
   * @access    protected
   */
   protected $source_height;
   
  /**
   * Resize To Width
   *
   * This is initially set via the {@link setResizeSize()} method which is called by
   * the {@link resizeImage()} method.  It then may be changed inside the
   * {@link resizeImage()} method to adjust for the aspect ratio depending on the
   * photo's orientation (Horizontal or Vertical).
   *
   * @var       string
   * @access    protected
   */
   protected $resize_to_width;
   
  /**
   * Resize To Height
   *
   * This is initially set via the {@link setResizeSize()} method which is called by
   * the {@link resizeImage()} method.  It then may be changed inside the
   * {@link resizeImage()} method to adjust for the aspect ratio depending on the
   * photo's orientation (Horizontal or Vertical).
   *
   * @var       string
   * @access    protected
   */
   protected $resize_to_height;
   
  /**
   * Resize If Source Image Is Smaller
   *
   * This property determines whether or not to resize the image if the source image
   * is smaller than the resize size.  This is set by the classes' {@link __construct()}
   * method.
   *
   * @var       bool
   * @access    protected
   */
   protected $resize_if_smaller;
   
  /**
   * JPEG Quality - Default Value
   *
   * This is the new image's quality setting, a value from 0-100 with 0 being the
   * highest compression/lowest quality.
   * This value is only used if the user doesn't specify a different value in
   * the {@link __construct()} method.
   *
   * @var       int     A number from 0-100 stating the image quality to use
   * @access    public
   */
   public $jpg_quality;
   
  /**
   * Black & White Filter
   *
   * This is an array set by the {@link setBWFilter()} method that has two keys,<br />
   * "use" -      bool -    true if imagefilter() should be used to create a 
   *                        grey-scale image and false (default) to not use it.<br />
   * "contrast" - integer - A number from -100 to 100 determining the contrast that
   *                        should be applied to it.  0 (default) is none and 
   *                        negative numbers are more contrast while positive numbers
   *                        lessen the contrast.
   *
   * @var       array
   * @access    public
   */
   public $bw_filter = array("use" => false);
   
  /**
   *  New File Permissions - (Linux Only!)
   *
   * By default, the file created by the script will have the script as the owner
   * (usually www-data) and you cannot modify, delete, or edit it directly.
   * You can use this to alter the permissions for multiple reasons which could also
   * be used to prevent files being over-written.
   *
   * Currently this isn't used, but was added as an idea at the time of writing this
   * method for future use.
   *
   * Possible values are as follows:<br />
   *      - 400 Owner Read
   *      - 200 Owner Write
   *      - 100 Owner Execute
   *      - 40 Group Read
   *      - 20 Group Write
   *      - 10 Group Execute
   *      - 4 Global Read
   *      - 2 Global Write
   *      - 1 Global Execute
   *
   *  default is 755
   */ 
   protected $chmod;    
   
  /**
    * Debug Mode
    *
    * Determines if caught exceptions should be displayed to screen (true) or 
    * written to the error log (false).
    *
    */
    const DEBUG = true;
    
    
   /**
    * Class Constructor
    *
    * Sets all values for the class to use for resizing images.
    * Some settings can also be set via the class methods.
    *
    * <code>
    *   $settings = array(
    *                   'jpg_quality' => 90,
    *                   'resize_if_smaller => true,
    *               };
    * </code>
    *
    * @param    array   $settings       Override values for the class properties
    * @return   void
    * @access   public
    */
    public function __construct($settings = array())
    {
        // Check values have been set
        if( count($settings) > 0 )
        {
            foreach( $settings as $val )
            {
                // Throw an exception if a value is empty
                if( empty($val) && !is_bool($val) )
                {
                    throw new Exception("Values for `\$settings` parameter cannot " . 
                        "be empty!");
                }
            }
        }

        $this->jpg_quality = isset($settings['jpg_quality']) ? 
            $settings['jpg_quality'] : 85;
            
        $this->resize_if_smaller = isset($settings['resize_if_smaller']) ? 
            $settings['resize_if_smaller'] : false;
    }

    /**
    * Set Resize Size
    *
    * This is a very simple method created for minimizing syntax in the primary
    * method's code, {@link resizeImage()}.
    * It simply assigns the values given as $params to {@link $resize_to_width} and
    * to {@link $resize_to_height}.
    *
    * @param     integer     Width to resize image to.
    * @param     integer     Height to resize image to.
    * @return    void
    * @access    private
    */
    private function setResizeSize($width, $height)
    {
        $this->resize_to_width = $width;
        $this->resize_to_height = $height;
    }

    /**
    * Set Black & White Filter
    *
    * This method is used to set the values that effect the output of the imagefilter()
    * function and also determines whether or not to use the black and white filter.
    * This could be further adapted to allow control over brightness.
    *
    * This method will throw a M5Exception if the imagefilter() function isn't available.
    *
    * @param     bool    true if using B&W filter, false (default) if not
    * @param     integer A numeric value from -100 to 100 determining what level of
    *                    contrast to apply. The more negative the number, the higher
    *                    the contrast.  The default is "4".  See {@link $bw_filter}
    *                    for more details.
    * @return    void
    * @access    public
    */
    public function setBWFilter( $use=false, $contrast=4 )
    {
        try
        {
            if( !function_exists('imagefilter') )
            {
                throw new M5Exception("Your GD library version does not support " . 
                    "the `imagefilter` function.  Black & White conversions aren't " . 
                    "available.");
            }
        }
        catch( M5Exception $e )
        {
            $this->handleException($e);
            return;
        }
        $this->bw_filter = array("use" => $use, "contrast" => $contrast);
    }

   /**
    * Set Image Information
    *
    * This method is used to set {@link $mime_type} and {@link $exif_data} using PHP's
    * EXIF library.
    * It will set {@link $error} if it is unable to execute successfully.  
    *
    * @return    none
    * @access    private
    */
    private function setImageInfo()
    {
        try
        {
            // Check if exif_imagetype function exists
            if( !function_exists('exif_imagetype') )
            {
                // Try using a work-around
                if( list($width, $height, $exif_type, $attr) = getimagesize( $this->source_file ) 
                    === false )
                {
                    throw new M5Exception('The `exif_imagetype` function could not ' .
                    'be found and the workaround failed. ' . 
                    'Verify the value of $source_file is a valid image or disable ' . 
                    'calls to the `setImageInfo` method.');
                }
            }
            // Try to get the mime type
            elseif( !$exif_type = exif_imagetype( $this->source_file ) )
            {
                // Throw Exception stating the problem
                throw new M5Exception('A valid mimetype couldn\'t be found for `' . 
                    $this->source_file .'`.  Verify the file IS a valid image file.');
            }
            
            // Check if image_type_to_mime_type() function exists
            if( !function_exists('image_type_to_mime_type') )
            {
                throw new Exception("The function `image_type_to_mime_type` couldn't " .
                    "be found.  Please correct this or disable it in the `setImageInfo()`" .
                    " method to avoid further errors.");
            }
            
        }
        catch( M5Exception $e )
        {
            $this->handleException($e);
            return false;
        }
      
        // Set mimetype
        $this->mime_type = image_type_to_mime_type($exif_type);

        // Assign exif_data array to property.
        $this->exif_data = exif_read_data($this->source_file);
        
        return true;
    }

   /**
    * Resize Image Method
    *
    * This is the primary method in this class.  This is used to resize and/or
    * perform any filtering options (such as the B&W filter), then create a new
    * image as the result.
    *
    * The last parameter, $quality, is an optional override for the one set in 
    * the class {@link __construct()} method.
    *
    * @param    string  $file       The relative or absolute path and image
    *                               filename of the file being converted.
    * @param    string  $target     The relative or absolute path and image
    *                               filename of the new file location.
    * @param    integer $width      The desired new maximum width.  This will be 
    *                               dis-regarded if the $height parameter is larger.
    * @param    integer $height     The desired new maximum height.  This will be 
    *                               dis-regarded if the $width parameter is larger.
    * @param    integer $quality    An integer between 0-100 to determine the image 
    *                               quality to use for the image resize.  This is 
    *                               optional and an override to {@link JPG_QUALITY}.
    * @return   bool                True on success, False on failure.
    * @access   public
    * @TODO     Adapt $quality for PNG compression, add cropping capabilities instead
    *           of auto-aspect ratio, and add other image formats.
    */
    public function resizeImage($file, $target, $width, $height, $quality=null)
    {
        // Set file properties
        $this->source_file = $file;
        $this->target_file = $target;
      
        // Set resize sizes
        $this->setResizeSize($width, $height);
      
        // Set image info
        $this->setImageInfo();
      
        // Set image quality if it was defined
        if($quality !== null)
        {
            $this->jpg_quality = $quality;
        }
      
        // Error Checking
        try
        {
            // Check source file exists
            if ( !file_exists($this->source_file) )
            {
                throw new M5Exception("The source file cannot be found!  Check the " . 
                    "file `$file` and make sure it exists.");
            }
            // Check source file is readable
            elseif (!is_readable($this->source_file))
            {
                throw new M5Exception("The source file `$file` is not readable." . 
                    " Check the permissions for this file.");
            }
            // If target and source file is the same, check that it is writable
            elseif ($this->source_file === $this->target_file && !is_writable($this->source_file))
            {
                throw new M5Exception("The source file and target file `$file` aren't " .
                    "writable!  Check the permissions for this file.");
            }
        }
        catch( M5Exception $e )
        {
            $this->handleException($e);
            return false;
        }
        
        try
        {
            // Validate mime_type
            if($this->mime_type == 'image/jpeg')
            {
                // Create image from JPEG source file
                if( !$image = @imagecreatefromjpeg($this->source_file) )
                {
                    $error = error_get_last();
                    throw new M5Exception($error['message']);
                }
            }
        }
        catch( M5Exception $e )
        {
            $this->handleException($e);
            return false;
        }
         
       /**
        * Set image size ( THIS MUST be done like this, otherwise image 
        *   won't be correct size in output!)
        */
        $this->source_width = imageSX($image);
        $this->source_height = imageSY($image);
         
        // Horizontal Image New Size
        if( $this->source_width > $this->source_height )
        {
            // Redefine the resize_to_height based on image aspect ratio.
            $this->resize_to_height = round($this->resize_to_width/($this->source_width/$this->source_height));
        }
        
        // Vertical Image New Size
        if( $this->source_height > $this->source_width )
        {
            // Redefine the resize_to_width based on image aspect ratio.
            $this->resize_to_width = round($this->resize_to_height/($this->source_height/$this->source_width));
        }
        
        // Square Image New Size
        if( $this->source_height === $this->source_width )
        {
            // Set square resize
            if( $this->resize_to_width !== $this->resize_to_height )
            {
                // If the resize width is larger, then set height to match.
                if( $this->resize_to_width > $this->resize_to_height )
                {
                    $this->resize_to_width = $this->resize_to_height;
                }
                
                // If the resize height is larger, then set width to match.
                elseif( $this->resize_to_width < $this->resize_to_height )
                {
                    $this->resize_to_height = $this->resize_to_width;
                }
            }
        }
        // Check width and height of source image compared to resized size
        if( $this->resize_if_smaller === false )
        {
           /**
            * If this fails, check the setting of `$resize_to_height` and 
            * `$resize_to_width` and make sure they're being set correctly.
            */
            try
            {
                // If image and not large enough, throw Exception
                if( $this->source_height < $this->resize_to_height || 
                    $this->source_width < $this->resize_to_width )
                {
                    throw new M5Exception("The source image is smaller than the " . 
                        "resize size.  The source image is `{$this->source_width}x" . 
                        "{$this->source_height}` and the resize size is " . 
                        "`{$this->resize_to_width}x{$this->resize_to_height}`.");
                }
            }
            catch( M5Exception $e )
            {
                $this->handleException($e);
                return false;
            }
        }
         
        try
        {
           /**
            * Create New Blank Image
            * If this fails, check your GD library version.  At LEAST 2.0.1 or
            * later is required.  If you are using at least PHP 5.2.4, the GD
            * version should be included in the error message.
            */
            if( !$new_image = @ImageCreateTrueColor($this->resize_to_width, 
                $this->resize_to_height) )
            {
                $error = error_get_last();
                $msg = is_defined('GD_VERSION') ? " GD VERSION: " . GD_VERSION : '';
                throw new M5Exception($error['message'] . $msg);
            }
            
            /**
             * Resize & Resample Image
             * 
             * ** NOTE: When converting this method to create cropped images,
             *    see the notes at http://php.net/manual/function.imagecopyresampled.php
             */
            if( !@imagecopyresampled($new_image, $image, 0, 0, 0, 0, 
                    $this->resize_to_width, $this->resize_to_height, 
                    $this->source_width, $this->source_height) )
            {
                $error = error_get_last();
                throw new M5Exception( $error['message'] );
            }
         
            // Apply B&W filter if "use" is set to true for it.
            if( $this->bw_filter['use'] == true )
            {
                if( !@imagefilter($new_image, IMG_FILTER_GRAYSCALE) ||
                    !@imagefilter($new_image, IMG_FILTER_CONTRAST, $this->bw_filter['contrast']) )
                {
                    $error = error_get_last();
                    throw new M5Exception( "Black & White conversion filter failed. " . 
                        $error['message'] );
                }
            } 
            
            // Check if we need to create a JPEG target file
            if( $this->mime_type == 'image/jpeg' )
            {
                // Create the target file
                if( @imagejpeg($new_image, $this->target_file, $this->jpg_quality) )
                {
                    // Destroy image resources
                    imagedestroy($new_image);
                    imagedestroy($image);
                    return true;
                }
                else
                {
                    $error = error_get_last();
                    throw new M5Exception("JPEG image creation failed. " . $error['message'] );
                }
            }
            else
            {
                // If the mime_type isn't image/jpeg, it shouldn't reach this....
                throw new M5Exception("An unsupported mimetype has been encountered. " . 
                    "See line `". __LINE__ ."` in file `". __FILE__ ."`.");
            } 
        }
        catch( M5Exception $e )
        {
            $this->handleException($e);
            return false;
        }
    }

   /**
    * Exception Handler
    *
    * This is used to determine how caught Exceptions in this class are handled
    * based on the {@link self::DEBUG} constant.
    *
    * @param    object      $e      Exception object
    * @return   void
    * @access protected
    */
    protected function handleException($e)
    {
        if( self::DEBUG )
        {
            echo $e->getMessage();
        }
        else
        {
            error_log( $e->getMessage() );
        }
    }
}

class M5Exception extends Exception{}
