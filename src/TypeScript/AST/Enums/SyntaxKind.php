<?php

declare(strict_types=1);

namespace ResourceParserGenerator\TypeScript\AST\Enums;

enum SyntaxKind: int
{
    case Unknown = 0;
    case EndOfFileToken = 1;
    case SingleLineCommentTrivia = 2;
    case MultiLineCommentTrivia = 3;
    case NewLineTrivia = 4;
    case WhitespaceTrivia = 5;
    case ShebangTrivia = 6;
    case ConflictMarkerTrivia = 7;
    case NonTextFileMarkerTrivia = 8;
    case NumericLiteral = 9;
    case BigIntLiteral = 10;
    case StringLiteral = 11;
    case JsxText = 12;
    case JsxTextAllWhiteSpaces = 13;
    case RegularExpressionLiteral = 14;
    case NoSubstitutionTemplateLiteral = 15;
    case TemplateHead = 16;
    case TemplateMiddle = 17;
    case TemplateTail = 18;
    case OpenBraceToken = 19;
    case CloseBraceToken = 20;
    case OpenParenToken = 21;
    case CloseParenToken = 22;
    case OpenBracketToken = 23;
    case CloseBracketToken = 24;
    case DotToken = 25;
    case DotDotDotToken = 26;
    case SemicolonToken = 27;
    case CommaToken = 28;
    case QuestionDotToken = 29;
    case LessThanToken = 30;
    case LessThanSlashToken = 31;
    case GreaterThanToken = 32;
    case LessThanEqualsToken = 33;
    case GreaterThanEqualsToken = 34;
    case EqualsEqualsToken = 35;
    case ExclamationEqualsToken = 36;
    case EqualsEqualsEqualsToken = 37;
    case ExclamationEqualsEqualsToken = 38;
    case EqualsGreaterThanToken = 39;
    case PlusToken = 40;
    case MinusToken = 41;
    case AsteriskToken = 42;
    case AsteriskAsteriskToken = 43;
    case SlashToken = 44;
    case PercentToken = 45;
    case PlusPlusToken = 46;
    case MinusMinusToken = 47;
    case LessThanLessThanToken = 48;
    case GreaterThanGreaterThanToken = 49;
    case GreaterThanGreaterThanGreaterThanToken = 50;
    case AmpersandToken = 51;
    case BarToken = 52;
    case CaretToken = 53;
    case ExclamationToken = 54;
    case TildeToken = 55;
    case AmpersandAmpersandToken = 56;
    case BarBarToken = 57;
    case QuestionToken = 58;
    case ColonToken = 59;
    case AtToken = 60;
    case QuestionQuestionToken = 61;
    /** Only the JSDoc scanner produces BacktickToken. The normal scanner produces NoSubstitutionTemplateLiteral and related kinds. */
    case BacktickToken = 62;
    /** Only the JSDoc scanner produces HashToken. The normal scanner produces PrivateIdentifier. */
    case HashToken = 63;
    case EqualsToken = 64;
    case PlusEqualsToken = 65;
    case MinusEqualsToken = 66;
    case AsteriskEqualsToken = 67;
    case AsteriskAsteriskEqualsToken = 68;
    case SlashEqualsToken = 69;
    case PercentEqualsToken = 70;
    case LessThanLessThanEqualsToken = 71;
    case GreaterThanGreaterThanEqualsToken = 72;
    case GreaterThanGreaterThanGreaterThanEqualsToken = 73;
    case AmpersandEqualsToken = 74;
    case BarEqualsToken = 75;
    case BarBarEqualsToken = 76;
    case AmpersandAmpersandEqualsToken = 77;
    case QuestionQuestionEqualsToken = 78;
    case CaretEqualsToken = 79;
    case Identifier = 80;
    case PrivateIdentifier = 81;
    case BreakKeyword = 83;
    case CaseKeyword = 84;
    case CatchKeyword = 85;
    case ClassKeyword = 86;
    case ConstKeyword = 87;
    case ContinueKeyword = 88;
    case DebuggerKeyword = 89;
    case DefaultKeyword = 90;
    case DeleteKeyword = 91;
    case DoKeyword = 92;
    case ElseKeyword = 93;
    case EnumKeyword = 94;
    case ExportKeyword = 95;
    case ExtendsKeyword = 96;
    case FalseKeyword = 97;
    case FinallyKeyword = 98;
    case ForKeyword = 99;
    case FunctionKeyword = 100;
    case IfKeyword = 101;
    case ImportKeyword = 102;
    case InKeyword = 103;
    case InstanceOfKeyword = 104;
    case NewKeyword = 105;
    case NullKeyword = 106;
    case ReturnKeyword = 107;
    case SuperKeyword = 108;
    case SwitchKeyword = 109;
    case ThisKeyword = 110;
    case ThrowKeyword = 111;
    case TrueKeyword = 112;
    case TryKeyword = 113;
    case TypeOfKeyword = 114;
    case VarKeyword = 115;
    case VoidKeyword = 116;
    case WhileKeyword = 117;
    case WithKeyword = 118;
    case ImplementsKeyword = 119;
    case InterfaceKeyword = 120;
    case LetKeyword = 121;
    case PackageKeyword = 122;
    case PrivateKeyword = 123;
    case ProtectedKeyword = 124;
    case PublicKeyword = 125;
    case StaticKeyword = 126;
    case YieldKeyword = 127;
    case AbstractKeyword = 128;
    case AccessorKeyword = 129;
    case AsKeyword = 130;
    case AssertsKeyword = 131;
    case AssertKeyword = 132;
    case AnyKeyword = 133;
    case AsyncKeyword = 134;
    case AwaitKeyword = 135;
    case BooleanKeyword = 136;
    case ConstructorKeyword = 137;
    case DeclareKeyword = 138;
    case GetKeyword = 139;
    case InferKeyword = 140;
    case IntrinsicKeyword = 141;
    case IsKeyword = 142;
    case KeyOfKeyword = 143;
    case ModuleKeyword = 144;
    case NamespaceKeyword = 145;
    case NeverKeyword = 146;
    case OutKeyword = 147;
    case ReadonlyKeyword = 148;
    case RequireKeyword = 149;
    case NumberKeyword = 150;
    case ObjectKeyword = 151;
    case SatisfiesKeyword = 152;
    case SetKeyword = 153;
    case StringKeyword = 154;
    case SymbolKeyword = 155;
    case TypeKeyword = 156;
    case UndefinedKeyword = 157;
    case UniqueKeyword = 158;
    case UnknownKeyword = 159;
    case FromKeyword = 160;
    case GlobalKeyword = 161;
    case BigIntKeyword = 162;
    case OverrideKeyword = 163;
    case OfKeyword = 164;
    case QualifiedName = 165;
    case ComputedPropertyName = 166;
    case TypeParameter = 167;
    case Parameter = 168;
    case Decorator = 169;
    case PropertySignature = 170;
    case PropertyDeclaration = 171;
    case MethodSignature = 172;
    case MethodDeclaration = 173;
    case ClassStaticBlockDeclaration = 174;
    case Constructor = 175;
    case GetAccessor = 176;
    case SetAccessor = 177;
    case CallSignature = 178;
    case ConstructSignature = 179;
    case IndexSignature = 180;
    case TypePredicate = 181;
    case TypeReference = 182;
    case FunctionType = 183;
    case ConstructorType = 184;
    case TypeQuery = 185;
    case TypeLiteral = 186;
    case ArrayType = 187;
    case TupleType = 188;
    case OptionalType = 189;
    case RestType = 190;
    case UnionType = 191;
    case IntersectionType = 192;
    case ConditionalType = 193;
    case InferType = 194;
    case ParenthesizedType = 195;
    case ThisType = 196;
    case TypeOperator = 197;
    case IndexedAccessType = 198;
    case MappedType = 199;
    case LiteralType = 200;
    case NamedTupleMember = 201;
    case TemplateLiteralType = 202;
    case TemplateLiteralTypeSpan = 203;
    case ImportType = 204;
    case ObjectBindingPattern = 205;
    case ArrayBindingPattern = 206;
    case BindingElement = 207;
    case ArrayLiteralExpression = 208;
    case ObjectLiteralExpression = 209;
    case PropertyAccessExpression = 210;
    case ElementAccessExpression = 211;
    case CallExpression = 212;
    case NewExpression = 213;
    case TaggedTemplateExpression = 214;
    case TypeAssertionExpression = 215;
    case ParenthesizedExpression = 216;
    case FunctionExpression = 217;
    case ArrowFunction = 218;
    case DeleteExpression = 219;
    case TypeOfExpression = 220;
    case VoidExpression = 221;
    case AwaitExpression = 222;
    case PrefixUnaryExpression = 223;
    case PostfixUnaryExpression = 224;
    case BinaryExpression = 225;
    case ConditionalExpression = 226;
    case TemplateExpression = 227;
    case YieldExpression = 228;
    case SpreadElement = 229;
    case ClassExpression = 230;
    case OmittedExpression = 231;
    case ExpressionWithTypeArguments = 232;
    case AsExpression = 233;
    case NonNullExpression = 234;
    case MetaProperty = 235;
    case SyntheticExpression = 236;
    case SatisfiesExpression = 237;
    case TemplateSpan = 238;
    case SemicolonClassElement = 239;
    case Block = 240;
    case EmptyStatement = 241;
    case VariableStatement = 242;
    case ExpressionStatement = 243;
    case IfStatement = 244;
    case DoStatement = 245;
    case WhileStatement = 246;
    case ForStatement = 247;
    case ForInStatement = 248;
    case ForOfStatement = 249;
    case ContinueStatement = 250;
    case BreakStatement = 251;
    case ReturnStatement = 252;
    case WithStatement = 253;
    case SwitchStatement = 254;
    case LabeledStatement = 255;
    case ThrowStatement = 256;
    case TryStatement = 257;
    case DebuggerStatement = 258;
    case VariableDeclaration = 259;
    case VariableDeclarationList = 260;
    case FunctionDeclaration = 261;
    case ClassDeclaration = 262;
    case InterfaceDeclaration = 263;
    case TypeAliasDeclaration = 264;
    case EnumDeclaration = 265;
    case ModuleDeclaration = 266;
    case ModuleBlock = 267;
    case CaseBlock = 268;
    case NamespaceExportDeclaration = 269;
    case ImportEqualsDeclaration = 270;
    case ImportDeclaration = 271;
    case ImportClause = 272;
    case NamespaceImport = 273;
    case NamedImports = 274;
    case ImportSpecifier = 275;
    case ExportAssignment = 276;
    case ExportDeclaration = 277;
    case NamedExports = 278;
    case NamespaceExport = 279;
    case ExportSpecifier = 280;
    case MissingDeclaration = 281;
    case ExternalModuleReference = 282;
    case JsxElement = 283;
    case JsxSelfClosingElement = 284;
    case JsxOpeningElement = 285;
    case JsxClosingElement = 286;
    case JsxFragment = 287;
    case JsxOpeningFragment = 288;
    case JsxClosingFragment = 289;
    case JsxAttribute = 290;
    case JsxAttributes = 291;
    case JsxSpreadAttribute = 292;
    case JsxExpression = 293;
    case JsxNamespacedName = 294;
    case CaseClause = 295;
    case DefaultClause = 296;
    case HeritageClause = 297;
    case CatchClause = 298;
    case AssertClause = 299;
    case AssertEntry = 300;
    case ImportTypeAssertionContainer = 301;
    case PropertyAssignment = 302;
    case ShorthandPropertyAssignment = 303;
    case SpreadAssignment = 304;
    case EnumMember = 305;
    /** @deprecated */
    case UnparsedPrologue = 306;
    /** @deprecated */
    case UnparsedPrepend = 307;
    /** @deprecated */
    case UnparsedText = 308;
    /** @deprecated */
    case UnparsedInternalText = 309;
    /** @deprecated */
    case UnparsedSyntheticReference = 310;
    case SourceFile = 311;
    case Bundle = 312;
    /** @deprecated */
    case UnparsedSource = 313;
    /** @deprecated */
    case InputFiles = 314;
    case JSDocTypeExpression = 315;
    case JSDocNameReference = 316;
    case JSDocMemberName = 317;
    case JSDocAllType = 318;
    case JSDocUnknownType = 319;
    case JSDocNullableType = 320;
    case JSDocNonNullableType = 321;
    case JSDocOptionalType = 322;
    case JSDocFunctionType = 323;
    case JSDocVariadicType = 324;
    case JSDocNamepathType = 325;
    case JSDoc = 326;
    case JSDocText = 327;
    case JSDocTypeLiteral = 328;
    case JSDocSignature = 329;
    case JSDocLink = 330;
    case JSDocLinkCode = 331;
    case JSDocLinkPlain = 332;
    case JSDocTag = 333;
    case JSDocAugmentsTag = 334;
    case JSDocImplementsTag = 335;
    case JSDocAuthorTag = 336;
    case JSDocDeprecatedTag = 337;
    case JSDocClassTag = 338;
    case JSDocPublicTag = 339;
    case JSDocPrivateTag = 340;
    case JSDocProtectedTag = 341;
    case JSDocReadonlyTag = 342;
    case JSDocOverrideTag = 343;
    case JSDocCallbackTag = 344;
    case JSDocOverloadTag = 345;
    case JSDocEnumTag = 346;
    case JSDocParameterTag = 347;
    case JSDocReturnTag = 348;
    case JSDocThisTag = 349;
    case JSDocTypeTag = 350;
    case JSDocTemplateTag = 351;
    case JSDocTypedefTag = 352;
    case JSDocSeeTag = 353;
    case JSDocPropertyTag = 354;
    case JSDocThrowsTag = 355;
    case JSDocSatisfiesTag = 356;
    case SyntaxList = 357;
    case NotEmittedStatement = 358;
    case PartiallyEmittedExpression = 359;
    case CommaListExpression = 360;
    case SyntheticReferenceExpression = 361;
    case Count = 362;

    public function isModifier(): bool
    {
        return $this === self::AbstractKeyword
            || $this === self::AccessorKeyword
            || $this === self::AsyncKeyword
            || $this === self::ConstKeyword
            || $this === self::DeclareKeyword
            || $this === self::DefaultKeyword
            || $this === self::ExportKeyword
            || $this === self::InKeyword
            || $this === self::OutKeyword
            || $this === self::OverrideKeyword
            || $this === self::PrivateKeyword
            || $this === self::ProtectedKeyword
            || $this === self::PublicKeyword
            || $this === self::ReadonlyKeyword
            || $this === self::StaticKeyword;
    }
}