<?php
/**
 * @author Dmitry Gladyshev <deel@email.ru>
 */

namespace Rucaptcha;


use Rucaptcha\Exception\InvalidArgumentException;

trait ConfigurableTrait
{
    public function setOptions(array $properties, $ignoreMissingProperties = false)
    {
        foreach ($properties as $property => $value) {
            $setter = 'set' . ucfirst($property);

            if (method_exists($this, $setter)) {
                $this->$setter($value);
                continue;
            }

            if (property_exists($this, $property)) {
                $this->$property = $value;
                continue;
            }

            if (!$ignoreMissingProperties) {
                throw new InvalidArgumentException("Property `{$property}` not found in class `" . __CLASS__ . "`.");
            }
        }
        return $this;
    }
}