<?php

declare(strict_types=1);

namespace ResourceParserGenerator\TypeScript\AST\Enums;

enum NodeFlag: int
{
    case None = 0;
    case Let = 1;
    case Const = 2;
    case NestedNamespace = 4;
    case Synthesized = 8;
    case Namespace = 16;
    case OptionalChain = 32;
    case ExportContext = 64;
    case ContainsThis = 128;
    case HasImplicitReturn = 256;
    case HasExplicitReturn = 512;
    case GlobalAugmentation = 1024;
    case HasAsyncFunctions = 2048;
    case DisallowInContext = 4096;
    case YieldContext = 8192;
    case DecoratorContext = 16384;
    case AwaitContext = 32768;
    case DisallowConditionalTypesContext = 65536;
    case ThisNodeHasError = 131072;
    case JavaScriptFile = 262144;
    case ThisNodeOrAnySubNodesHasError = 524288;
    case HasAggregatedChildData = 1048576;
    case JSDoc = 8388608;
    case JsonFile = 67108864;
    case BlockScoped = 3;
    case ReachabilityCheckFlags = 768;
    case ReachabilityAndEmitFlags = 2816;
    case ContextFlags = 50720768;
    case TypeExcludesFlags = 40960;
}
