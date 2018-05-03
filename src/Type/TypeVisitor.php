<?php

declare(strict_types=1);

/*
 * Copyright 2016 Johannes M. Schmitt <schmittjoh@gmail.com>
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 *     http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */

namespace JMS\Serializer\Type;

use Hoa\Compiler\Llk\TreeNode;
use Hoa\Visitor\Element;
use Hoa\Visitor\Visit;
use JMS\Serializer\Type\Exception\InvalidNode;
use function strpos;

final class TypeVisitor implements Visit
{
    public function visit(Element $element, &$handle = null, $eldnah = null)
    {
        switch ($element->getId()) {
            case '#simple_type' :
                return $this->visitSimpleType($element);
            case '#compound_type' :
                return $this->visitCompoundType($element, $handle, $eldnah);
        }

        throw new InvalidNode();
    }

    /**
     * @return string|mixed[]
     */
    private function visitSimpleType(TreeNode $element)
    {
        $tokenNode = $element->getChild(0);
        $token = $tokenNode->getValueToken();
        $value = $tokenNode->getValueValue();

        if ($token === 'name') {
            return ['name' => $value, 'params' => []];
        }

        $escapeChar = $token === 'quoted_string' ? '"' : "'";

        if (strpos($value, $escapeChar) === false) {
            return $value;
        }

        return str_replace($escapeChar . $escapeChar, $escapeChar, $value);
    }

    private function visitCompoundType(TreeNode $element, ?int &$handle, ?int $eldnah) : array
    {
        $nameToken = $element->getChild(0);
        $parameters = array_slice($element->getChildren(), 1);

        return [
            'name' => $nameToken->getValueValue(),
            'params' => array_map(
                function (TreeNode $node) use ($handle, $eldnah) {
                    return $node->accept($this, $handle, $eldnah);
                },
                $parameters
            ),
        ];
    }
}