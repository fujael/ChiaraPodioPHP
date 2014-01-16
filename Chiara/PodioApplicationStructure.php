<?php
namespace Chiara;
/**
 * this class is used to access podio applications, or as a blueprint for items to help when validating
 * changes to a podio item
 */
class PodioApplicationStructure
{
    const APPNAME = '';
    /**
     * Use this variable to define your application's structure offline
     *
     * The structure array is used to provide metadata about fields.  The important information is what
     * kind of field is associated with an external_id or a field id.  This allows easy validation and
     * retrieval of fields.
     */
    protected $structure = array();

    /**
     * A map of applications to their structures, useful for retrieving new objects
     */
    static private $structures = array();

    function __construct()
    {
        if (count($this->structure)) {
            if (!static::APPNAME) {
                // TODO: convert this to a Chiara-specific exception
                throw new \Exception('Error: the APPNAME constant must be overridden and set to the app\'s name');
            }
            self::$structures[static::APPNAME] = array($this->structure, get_class($this));
        } elseif (static::APPNAME && isset(self::$structures[static::APPNAME])) {
            $this->structure = self::$structures[static::APPNAME][0];
        }
    }

    /**
     * useful when constructing your application
     */
    function dumpStructure()
    {
        return var_export($this->structure, 1);
    }

    function addTextField($name, $id)
    {
        $this->addField('text', $name, $id);
    }

    function addNumberField($name, $id)
    {
        $this->addField('number', $name, $id);
    }

    function addImageField($name, $id)
    {
        $this->addField('image', $name, $id);
    }

    function addDateField($name, $id)
    {
        $this->addField('file', $name, $id);
    }

    function addAppField($name, $id, array $referenceable_types)
    {
        $this->addField('app', $name, $id, $referenceable_types);
    }

    function addMoneyField($name, $id, array $allowed_currencies)
    {
        $this->addField('money', $name, $id, $allowed_currencies);
    }

    function addProgressField($name, $id)
    {
        $this->addField('progress', $name, $id);
    }

    function addLocationField($name, $id)
    {
        $this->addField('location', $name, $id);
    }

    function addDurationField($name, $id)
    {
        $this->addField('duration', $name, $id);
    }

    function addContactField($name, $id, $type)
    {
        if (!in_array($type, array('space_users', 'all_users', 'space_contacts', 'space_users_and_contacts'))) {
            // TODO: convert to custom Chiara exception
            throw new \Exception('Invalid type for contact field "' . $name . '" in app ' . static::APPNAME);
        }
        $this->addField('contact', $name, $id, $type);
    }

    function addCalculationField($name, $id)
    {
        $this->addField('calculation', $name, $id);
    }

    function addEmbedField($name, $id)
    {
        $this->addField('embed', $name, $id);
    }

    function addQuestionField($name, $id, array $options, $multiple)
    {
        $this->addField('file', $name, $id, array('options' => $options, 'multiple' => $multiple));
    }

    function addCategoryField($name, $id, array $options, $multiple)
    {
        $this->addField('category', $name, $id, array('options' => $options, 'multiple' => $multiple));
    }

    /**
     * The "file" field type only exists in legacy Podio apps
     */
    function addFileField($name, $id)
    {
        $this->addField('file', $name, $id);
    }

    /**
     * The "video" field type only exists in legacy Podio apps
     */
    function addVideoField($name, $id)
    {
        $this->addField('video', $name, $id);
    }

    /**
     * The "state" field type only exists in legacy Podio apps
     */
    function addStateField($name, $id, array $allowed_values)
    {
        $this->addField('state', $name, $id, $allowed_values);
    }

    /**
     * The "media" field type only exists in legacy Podio apps
     */
    function addMediaField($name, $id)
    {
        $this->addField('media', $name, $id);
    }

    function addField($type, $name, $id, $config = null)
    {
        $this->structure[$name] = array('type' => $type, 'name' => $name, 'id' => $id, 'config' => null);
        $this->structure[$id] = array('type' => $type, 'name' => $name, 'id' => $id, 'config' => null);
    }

    /**
     * translate a Podio app downloaded from the API into a structure object
     */
    function structureFromApp(PodioApp $app)
    {
        foreach ($app->fields as $field) {
            switch ($field->type()) {
                case 'state' :
                    $this->addStateField($field->external_id, $field->id, $field->allowed_values);
                    break;
                case 'app' :
                    $this->addAppField($field->external_id, $field->id, $field->referenceable_types);
                    break;
                case 'money' :
                    $this->addMoneyField($field->external_id, $field->id, $field->allowed_currencies);
                    break;
                case 'contact' :
                    $this->addContactField($field->external_id, $field->id, $field->contact_type);
                    break;
                case 'question' :
                    $this->addQuestionField($field->external_id, $field->id, $field->options, $field->multiple);
                    break;
                case 'category' :
                    $this->addCategoryField($field->external_id, $field->id, $field->options, $field->multiple);
                    break;
                case 'text' :
                case 'number' :
                case 'image' :
                case 'media' :
                case 'date' :
                case 'progress' :
                case 'location' :
                case 'video' :
                case 'duration' :
                case 'calculation' :
                case 'embed' :
                case 'file' :
                default :
                    $this->addField($field->external_id, $field->id, $field->type);
                    break;
            }
        }
        self::$structures[$app->space_id . '/' . $app->app_id] = array($this->structure, get_class($this));
    }

    static function getStructure($appname, $strict = false, $overrideclassname = false)
    {
        if (!isset(self::$structures[$appname])) {
            if ($strict) {
                // TODO: convert this to a Chiara-specific exception
                throw new \Exception('No structure found for app "' . $appname . '"');
            }
            return new self;
        }
        $class = self::$structures[$appname][1];
        return new $class;
    }

    function getType($field)
    {
        if (isset($this->structure[$field])) {
            return $this->structure[$field]['type'];
        }
        throw new \Exception('Unknown field: "' . $field . '" requested for app ' . static::APPNAME);
    }

    function getConfig($field)
    {
        if (isset($this->structure[$field])) {
            return $this->structure[$field]['config'];
        }
        throw new \Exception('Unknown field: "' . $field . '" configuration requested for app ' . static::APPNAME);
    }

    function dump()
    {
        var_export($this->structure);
    }
}