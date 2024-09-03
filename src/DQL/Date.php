<?php 

namespace App\DQL;

use Doctrine\ORM\Query\Lexer;
use Doctrine\ORM\Query\AST\Functions\FunctionNode;

/**
 * "DATE" "(" SimpleArithmeticExpression ")". Modified from DoctrineExtensions\Query\Mysql\Year
 *
 * @category    DoctrineExtensions
 * @package     DoctrineExtensions\Query\Mysql
 * @author      Rafael Kassner <kassner@gmail.com>
 * @author      Sarjono Mukti Aji <me@simukti.net>
 * @author      Gustavo Luis
 * @license     MIT License
 */
class Date extends FunctionNode
{
    public $date;

    /**
     * @override
     */
    public function getSql(\Doctrine\ORM\Query\SqlWalker $sqlWalker)
    {
        return "DATE(" . $sqlWalker->walkArithmeticPrimary($this->date) . ")";
    }

    /**
     * @override
     */
    public function parse(\Doctrine\ORM\Query\Parser $parser)
    {
        $parser->match(Lexer::T_IDENTIFIER);
        $parser->match(Lexer::T_OPEN_PARENTHESIS);

        $this->date = $parser->ArithmeticPrimary();

        $parser->match(Lexer::T_CLOSE_PARENTHESIS);
    }
}