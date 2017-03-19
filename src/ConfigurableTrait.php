<?php
/**
 * @author Dmitry Gladyshev <deel@email.ru>
 */

namespace Rucaptcha;

use Rucaptcha\Exception\InvalidArgumentException;

trait ConfigurableTrait
{
    public function setOptions(array $options, $ignoreMissingOptions = false)
    {
        foreach ($options as $option => $value) {
            $setter = 'set' . ucfirst($option);

            if (method_exists($this, $setter)) {
                $this->$setter($value);
                continue;
            }

            if (property_exists($this, $option)) {
                $this->$option = $value;
                continue;
            }

            if (!$ignoreMissingOptions) {
                throw new InvalidArgumentException("Property `{$option}` not found in class `" . __CLASS__ . "`.");
            }
        }
    }
}
