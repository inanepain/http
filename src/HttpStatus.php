<?php

/**
 * Inane: Http
 *
 * Http client, request and response objects implementing psr-7 (message interfaces).
 *
 * $Id$
 * $Date$
 *
 * PHP version 8.5
 *
 * @author   Philip Michael Raab<philip@cathedral.co.za>
 * @package  inanepain\http
 * @category http
 *
 * @license  UNLICENSE
 * @license  https://unlicense.org/UNLICENSE UNLICENSE
 *
 * _version_ $version
 */

declare(strict_types = 1);

namespace Inane\Http;

use Inane\Stdlib\Enum\CoreEnumInterface;
use Inane\Stdlib\Enum\CoreEnumTrait;

/**
 * Http Status
 *
 *  - 1xx: Informational
 *  - 2xx: Success
 *  - 3xx: Redirection
 *  - 4xx: Client Error
 *  - 5xx: Server Error
 *
 * @version 1.1.0
 */
enum HttpStatus: int implements CoreEnumInterface {
    #region 1xx: Informational
    /**
     * 100
     */
    case Continue = 100;
    /**
     * 101
     */
    case SwitchingProtocols = 101;
    /**
     * 102
     */
    case Processing = 102;
    #endregion 1xx: Informational

    #region 2xx: Success
    /**
     * 200
     */
    case Ok = 200;
    /**
     * 201
     */
    case Created = 201;
    /**
     * 202
     */
    case Accepted = 202;
    /**
     * 203
     */
    case NonAuthoritativeInformation = 203;
    /**
     * 204
     */
    case NoContent = 204;
    /**
     * 205
     */
    case ResetContent = 205;
    /**
     * 206
     */
    case PartialContent = 206;
    /**
     * 207
     */
    case MultiStatus = 207;
    /**
     * 208
     */
    case AlreadyReported = 208;
    /**
     * 226
     */
    case ImUsed = 226;
    #endregion 2xx: Success

    #region 3xx: Redirection
    /**
     * 300
     */
    case MultipleChoices = 300;
    /**
     * 301
     */
    case MovedPermanently = 301;
    /**
     * 302
     */
    case Found = 302;
    /**
     * 303
     */
    case SeeOther = 303;
    /**
     * 304
     */
    case NotModified = 304;
    /**
     * 305
     */
    case UseProxy = 305;
    /**
     * 306
     */
    case SwitchProxy = 306;
    /**
     * 307
     */
    case TemporaryRedirect = 307;
    /**
     * 308
     */
    case PermanentRedirect = 308;
    /**
     * 308
     */
    //    case ResumeIncomplete;
    #endregion 3xx: Redirection

    #region 4xx: Client Error
    /**
     * 400
     */
    case BadRequest = 400;
    /**
     * 401
     */
    case Unauthorized = 401;
    /**
     * 402
     */
    case PaymentRequired = 402;
    /**
     * 403
     */
    case Forbidden = 403;
    /**
     * 404
     */
    case NotFound = 404;
    /**
     * 405
     */
    case MethodNotAllowed = 405;
    /**
     * 406
     */
    case NotAcceptable = 406;
    /**
     * 407
     */
    case ProxyAuthenticationRequired = 407;
    /**
     * 408
     */
    case RequestTimeout = 408;
    /**
     * 409
     */
    case Conflict = 409;
    /**
     * 410
     */
    case Gone = 410;
    /**
     * 411
     */
    case LengthRequired = 411;
    /**
     * 412
     */
    case PreconditionFailed = 412;
    /**
     * 413
     */
    case RequestEntityTooLarge = 413;
    /**
     * 414
     */
    case RequestUriTooLong = 414;
    /**
     * 415
     */
    case UnsupportedMediaType = 415;
    /**
     * 416
     */
    case RequestedRangeNotSatisfiable = 416;
    /**
     * 417
     */
    case ExpectationFailed = 417;
    /**
     * 418
     */
    case ImATeapot = 418;
    /**
     * 421
     */
    case MisdirectedRequest = 421;
    /**
     * 422
     */
    case UnprocessableEntity = 422;
    /**
     * 423
     */
    case Locked = 423;
    /**
     * 424
     */
    case FailedDependency = 424;
    /**
     * 424
     */
    //	case TooEarly;
    /**
     * 426
     */
    case UpgradeRequired = 426;
    /**
     * 428
     */
    case PreconditionRequired = 428;
    /**
     * 429
     */
    case TooManyRequests = 429;
    /**
     * 431
     */
    case RequestHeaderFieldsTooLarge = 431;
    /**
     * 440
     */
    case LoginTimeout = 440;
    /**
     * 444
     */
    case NoResponse = 444;
    /**
     * 449
     */
    case RetryWith = 449;
    /**
     * 450
     */
    case BlockedByWindowsParentalControls = 450;
    /**
     * 451
     */
    case UnavailableForLegalReasons = 451;
    /**
     * 451
     */
    //    case Redirect;
    /**
     * 494
     */
    case RequestHeaderTooLarge = 494;
    /**
     * 495
     */
    case CertError = 495;
    /**
     * 496
     */
    case NoCert = 496;
    /**
     * 497
     */
    case HttpToHttps = 497;
    /**
     * 498
     */
    case TokenExpiredInvalid = 498;
    /**
     * 499
     */
    case ClientClosedRequest = 499;
    /**
     * 499
     */
    //    case TokenRequired;
    #endregion 4xx: Client Error

    #region 5xx: Server Error
    /**
     * 500
     */
    case InternalServerError = 500;
    /**
     * 501
     */
    case NotImplemented = 501;
    /**
     * 502
     */
    case BadGateway = 502;
    /**
     * 503
     */
    case ServiceUnavailable = 503;
    /**
     * 504
     */
    case GatewayTimeout = 504;
    /**
     * 505
     */
    case HttpVersionNotSupported = 505;
    /**
     * 506
     */
    case VariantAlsoNegotiates = 506;
    /**
     * 507
     */
    case InsufficientStorage = 507;
    /**
     * 508
     */
    case LoopDetected = 508;
    /**
     * 509
     */
    case BandwidthLimitExceeded = 509;
    /**
     * 510
     */
    case NotExtended = 510;
    /**
     * 511
     */
    case NetworkAuthenticationRequired = 511;
    /**
     * 520
     */
    case UnknownError = 520;
    /**
     * 521
     */
    case WebServerIsDown = 521;
    /**
     * 522
     */
    case ConnectionTimedOut = 522;
    /**
     * 523
     */
    case OriginIsUnreachable = 523;
    /**
     * 524
     */
    case ATimeoutOccurred = 524;
    /**
     * 525
     */
    case SslHandshakeFailed = 525;
    /**
     * 526
     */
    case InvalidSslCertificate = 526;
    /**
     * 527
     */
    case RailgunError = 527;
    #endregion 5xx: Server Error

    use CoreEnumTrait;

    /**
     * Get HTTP Status Description
     *
     * @return string
     */
    public function message(): string {
        return match ($this) {
            #region 1xx: Informational
            static::Continue => 'The server has received the request headers, and that the client should proceed to send the request body.',
            static::SwitchingProtocols => 'The requester has asked the server to switch protocols and the server is acknowledging that it will do so.',
            static::Processing => 'The server has received and is processing the request, but no response is available yet.',
            #endregion 1xx: Informational

            #region 2xx: Success
            static::Ok => 'The standard response for successful HTTP requests.',
            static::Created => 'The request has been fulfilled, and a new resource has been created.',
            static::Accepted => 'The request has been accepted but has not been processed yet. This code does not guarantee that the request will process successfully.',
            static::NonAuthoritativeInformation => 'HTTP 1.1. The server successfully processed the request but is returning information from another source.',
            static::NoContent => 'The server accepted the request but is not returning any content. This is often used as a response to a DELETE request.',
            static::ResetContent => 'Similar to a 204 No Content response, but this response requires the requester to reset the document view.',
            static::PartialContent => 'The server is delivering only a portion of the content, as requested by the client via a range header.',
            static::MultiStatus => 'The message body that follows is an XML message and can contain a number of separate response codes, depending on how many sub-requests were made.',
            static::AlreadyReported => "The members of a DAV binding have already been enumerated in a previous reply to this request and aren't being included again.",
            static::ImUsed => 'The server has fulfilled a GET request for the resource, and the response is a representation representing the result of one or more instance-manipulations applied to the current instance.',
            #endregion 2xx: Success

            #region 3xx: Redirection
            static::MultipleChoices => 'There are multiple options that the client may follow.',
            static::MovedPermanently => 'The resource has been moved, and all further requests should reference its new URI.',
            static::Found => 'The HTTP 1.0 specification described this status as "Moved Temporarily", but popular browsers respond to this status similar to behaviour intended for 303. The resource can be retrieved by referencing the returned URI.',
            static::SeeOther => 'The resource can be retrieved by following another URI using the GET method. When received in response to a POST, PUT or DELETE, it can usually be assumed that the server processed the request successfully and is sending the client to an informational endpoint.',
            static::NotModified => "The resource has not been modified since the version specified in If-Modified-Since or If-Match headers. The resource won't be returned in the response body.",
            static::UseProxy => 'HTTP 1.1. The resource is only available through a proxy and the address is provided in the response.',
            static::SwitchProxy => 'Deprecated in HTTP 1.1. Used to mean that later requests should be sent using the specified proxy.',
            static::TemporaryRedirect => 'HTTP 1.1. The request should be repeated with the URI provided in the response, but future requests should still call the original URI.',
            static::PermanentRedirect => 'Experimental. The request and all future requests should be repeated with the URI provided in the response. The HTTP method is not allowed to be changed in the subsequent request.',
            //            static::ResumeIncomplete => 'This code is used in the Resumable HTTP Requests Proposal to resume aborted PUT or POST requests',
            #endregion 3xx: Redirection

            #region 4xx: Client Error
            static::BadRequest => 'The request could not be fulfilled due to the incorrect syntax of the request.',
            static::Unauthorized => 'The requester is not authorised to access the resource. This is similar to 403 but is used in cases where authentication is expected but has failed or has not been provided.',
            static::PaymentRequired => 'Reserved for future use. Some web services use this as an indication that the client has sent an excessive number of requests.',
            static::Forbidden => 'The request was formatted correctly, but the server is refusing to supply the requested resource. Unlike 401, authenticating will not make a difference in the server\'s response.',
            static::NotFound => 'The resource could not be found. This is often used as a catch-all for all invalid URIs requested by the server.',
            static::MethodNotAllowed => "The resource was requested using a method that isn't allowed. For example, requesting a resource via a POST method when the resource only supports the GET method.",
            static::NotAcceptable => "The resource is valid but can't be provided in a format specified in the Accept headers in the request.",
            static::ProxyAuthenticationRequired => 'Authentication is required with the proxy before requests can be fulfilled.',
            static::RequestTimeout => 'The server timed out waiting for a request from the client. The client is allowed to repeat the request.',
            static::Conflict => 'The request cannot be completed due to a conflict in the request parameters.',
            static::Gone => 'The resource is no longer available at the requested URI, and no redirection will be given.',
            static::LengthRequired => 'The request did not specify the length of its content as required by the resource.',
            static::PreconditionFailed => 'The server does not meet one of the preconditions specified by the client.',
            static::RequestEntityTooLarge => 'The request is larger than what the server is able to process.',
            static::RequestUriTooLong => 'The URI provided in the request is too long for the server to process. This is often used when too much data has been encoded into the URI of a GET request and a POST request should be used instead.',
            static::UnsupportedMediaType => "The client provided data with a media type that the server doesn't support.",
            static::RequestedRangeNotSatisfiable => "The client has asked for a portion of the resource, but the server can't supply that portion.",
            static::ExpectationFailed => "The server can't meet the requirements of the Expect request-header field.",
            static::ImATeapot => "Any attempt to brew coffee with a teapot should result in the error code \"418 I'm a teapot\". The resulting entity body MAY be short and stout.",
            static::MisdirectedRequest => "The request was directed at a server that isn't able to produce a response. This can be sent by a server not configured to produce responses for the combination of scheme and authority that are included in the request URI.",
            static::UnprocessableEntity => "The request was formatted correctly but can't be processed in its current form. Often used when the specified parameters fail validation errors.",
            static::Locked => "The requested resource was found but has been locked and won't be returned.",
            static::FailedDependency => 'The request failed due to a failure of a previous request.',
            //            static::TooEarly => 'Indicates that the server is unwilling to risk processing a request that might be replayed.',
            static::UpgradeRequired => 'The client should repeat the request using an upgraded protocol such as TLS 1.0.',
            static::PreconditionRequired => 'The origin server requires the request to be conditional.',
            static::TooManyRequests => 'The user has sent too many requests in a given amount of time ("rate limiting").',
            static::RequestHeaderFieldsTooLarge => 'The server is unwilling to process the request because its header fields are too large.',
            static::LoginTimeout => 'A Microsoft extension. Indicates that your session has expired.',
            static::NoResponse => 'Used in Nginx logs to indicate that the server has returned no information to the client and closed the connection (useful as a deterrent for malware).',
            static::RetryWith => 'A Microsoft extension. The request should be retried after performing the appropriate action.',
            static::BlockedByWindowsParentalControls => 'A Microsoft extension. This error is given when Windows Parental Controls are turned on and are blocking access to the given webpage.',
            static::UnavailableForLegalReasons => 'A server operator has received a legal demand to deny access to a resource or to a set of resources that includes the requested resource.',
            //            static::Redirect => 'Used in Exchange ActiveSync if there either is a more efficient server to use or the server cannot access the users\' mailbox.',
            static::RequestHeaderTooLarge => 'Nginx internal code is similar to 431, but it was introduced earlier in version 0.9.4 (on January 21, 2011).',
            static::CertError => 'Nginx internal code used when SSL client certificate error occurred to distinguish it from 4XX in a log and an error page redirection.',
            static::NoCert => "Nginx internal code used when the client didn't provide a certificate to distinguish it from 4XX in a log and an error page redirection.",
            static::HttpToHttps => 'Nginx internal code used for the plain HTTP requests that are sent to HTTPS port to distinguish it from 4XX in a log and an error page redirection.',
            static::TokenExpiredInvalid => 'Returned by ArcGIS for Server. A code of 498 indicates an expired or otherwise invalid token.',
            static::ClientClosedRequest => 'Used in Nginx logs to indicate when the connection has been closed by the client while the server is still processing its request, making the server unable to send a status code back.',
            //            static::TokenRequired => 'Returned by ArcGIS for Server. A code of 499 indicates that a token is required (if no token was submitted).',
            #endregion 4xx: Client Error

            #region 5xx: Server Error
            static::InternalServerError => 'A generic status for an error in the server itself.',
            static::NotImplemented => 'The server cannot respond to the request. This usually implies that the server could possibly support the request in the future — otherwise a 4xx status may be more appropriate.',
            static::BadGateway => "The server is acting as a proxy and didn't receive an acceptable response from the upstream server.",
            static::ServiceUnavailable => "The server is down and isn't accepting requests.",
            static::GatewayTimeout => "The server is acting as a proxy and didn't receive a response from the upstream server.",
            static::HttpVersionNotSupported => "The server doesn't support the HTTP protocol version specified in the request.",
            static::VariantAlsoNegotiates => 'Transparent content negotiation for the request results in a circular reference.',
            static::InsufficientStorage => "The user or server doesn't have enough storage quota to fulfil the request.",
            static::LoopDetected => 'The server detected an infinite loop in the request.',
            static::BandwidthLimitExceeded => "This status code isn't specified in any RFCs. Its use is unknown.",
            static::NotExtended => 'Further extensions to the request are necessary for it to be fulfilled.',
            static::NetworkAuthenticationRequired => 'The client must authenticate with the network before sending requests.',
            static::UnknownError => 'This status code isn\'t specified in any RFC and is returned by certain services, for instance, Microsoft Azure and CloudFlare servers: "The 520 error is essentially a "catch-all" response for when the origin server returns something unexpected or something that is not tolerated/interpreted (protocol violation or empty response)."',
            static::WebServerIsDown => 'The origin server has refused the connection from CloudFlare.',
            static::ConnectionTimedOut => "CloudFlare couldn't negotiate a TCP handshake with the origin server.",
            static::OriginIsUnreachable => 'CloudFlare could not reach the origin server; for example, if the DNS records for the origin server are incorrect.',
            static::ATimeoutOccurred => "CloudFlare was able to complete a TCP connection to the origin server but didn't receive a timely HTTP response.",
            static::SslHandshakeFailed => "CloudFlare couldn't negotiate an SSL/TLS handshake with the origin server.",
            static::InvalidSslCertificate => "CloudFlare couldn't validate the SSL/TLS certificate that the origin server presented.",
            static::RailgunError => 'The request timed out or failed after the WAN connection has been established.',
            #endregion 5xx: Server Error
            default => 'UNKNOWN!',
        };
    }

    /**
     * Get HTTP Status Message
     *
     * @return string
     */
    public function title(): string {
        return match ($this) {
            #region 1xx: Informational
            static::Continue => 'Continue',
            static::SwitchingProtocols => 'Switching protocols',
            static::Processing => 'Processing',
            #endregion 1xx: Informational

            #region 2xx: Success
            static::Ok => 'HTTP/1.1 200 OK', // FINAL
            static::Created => 'Created',
            static::Accepted => 'Accepted',
            static::NonAuthoritativeInformation => 'Non authoritative information',
            static::NoContent => 'No content',
            static::ResetContent => 'Reset content',
            static::PartialContent => 'HTTP/1.1 206 Partial Content', // FINAL
            static::MultiStatus => 'MULTI Status',
            static::AlreadyReported => 'Already reported',
            static::ImUsed => 'Im used',
            #endregion 2xx: Success

            #region 3xx: Redirection
            static::MultipleChoices => 'Multiple choices',
            static::MovedPermanently => 'Moved permanently',
            static::Found => 'Found',
            static::SeeOther => 'See other',
            static::NotModified => 'Not modified',
            static::UseProxy => 'Use proxy',
            static::SwitchProxy => 'Switch proxy',
            static::TemporaryRedirect => 'Temporary redirect',
            static::PermanentRedirect => 'Permanent redirect',
            //            static::ResumeIncomplete => 'Resume incomplete',
            #endregion 3xx: Redirection

            #region 4xx: Client Error
            static::BadRequest => 'Bad request',
            static::Unauthorized => 'Unauthorized',
            static::PaymentRequired => 'Payment required',
            static::Forbidden => 'Forbidden',
            static::NotFound => 'Not found',
            static::MethodNotAllowed => 'Method not allowed',
            static::NotAcceptable => 'Not acceptable',
            static::ProxyAuthenticationRequired => 'Proxy authentication required',
            static::RequestTimeout => 'Request timeout',
            static::Conflict => 'Conflict',
            static::Gone => 'Gone',
            static::LengthRequired => 'Length required',
            static::PreconditionFailed => 'Precondition failed',
            static::RequestEntityTooLarge => 'Request entity too large',
            static::RequestUriTooLong => 'REQUEST-URI TOO Long',
            static::UnsupportedMediaType => 'Unsupported media type',
            static::RequestedRangeNotSatisfiable => 'Requested range not satisfiable',
            static::ExpectationFailed => 'Expectation failed',
            static::ImATeapot => 'I\'m a teapot',
            static::MisdirectedRequest => 'Misdirected request',
            static::UnprocessableEntity => 'Unprocessable entity',
            static::Locked => 'Locked',
            static::FailedDependency => 'Failed dependency',
            //            static::TooEarly => 'Too early',
            static::UpgradeRequired => 'Upgrade required',
            static::PreconditionRequired => 'Precondition required',
            static::TooManyRequests => 'Too many requests',
            static::RequestHeaderFieldsTooLarge => 'Request header fields too large',
            static::LoginTimeout => 'Login timeout',
            static::NoResponse => 'No response',
            static::RetryWith => 'Retry with',
            static::BlockedByWindowsParentalControls => 'Blocked by windows parental controls',
            static::UnavailableForLegalReasons => 'Unavailable for legal reasons',
            //            static::Redirect => 'Redirect',
            static::RequestHeaderTooLarge => 'Request header too large',
            static::CertError => 'Cert error',
            static::NoCert => 'No cert',
            static::HttpToHttps => 'Http to https',
            static::TokenExpiredInvalid => 'Token expired invalid',
            static::ClientClosedRequest => 'Client closed request',
            //            static::TokenRequired => 'Token required',
            #endregion 4xx: Client Error

            #region 5xx: Server Error
            static::InternalServerError => 'Internal server error',
            static::NotImplemented => 'Not implemented',
            static::BadGateway => 'Bad gateway',
            static::ServiceUnavailable => 'Service unavailable',
            static::GatewayTimeout => 'Gateway timeout',
            static::HttpVersionNotSupported => 'Http version not supported',
            static::VariantAlsoNegotiates => 'Variant also negotiates',
            static::InsufficientStorage => 'Insufficient storage',
            static::LoopDetected => 'Loop detected',
            static::BandwidthLimitExceeded => 'Bandwidth limit exceeded',
            static::NotExtended => 'Not extended',
            static::NetworkAuthenticationRequired => 'Network authentication required',
            static::UnknownError => 'Unknown error',
            static::WebServerIsDown => 'Web server is down',
            static::ConnectionTimedOut => 'Connection timed out',
            static::OriginIsUnreachable => 'Origin is unreachable',
            static::ATimeoutOccurred => 'A timeout occurred',
            static::SslHandshakeFailed => 'Ssl handshake failed',
            static::InvalidSslCertificate => 'Invalid ssl certificate',
            static::RailgunError => 'Railgun error',
            #endregion 5xx: Server Error
            default => 'UNKNOWN!',
        };
    }
}
