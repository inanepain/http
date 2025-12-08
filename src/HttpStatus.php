<?php

/**
 * Inane: Http
 *
 * Http client, request and response objects implementing psr-7 (message interfaces).
 *
 * $Id$
 * $Date$
 *
 * PHP version 8.4
 *
 * @author Philip Michael Raab<philip@cathedral.co.za>
 * @package inanepain\http
 * @category http
 *
 * @license UNLICENSE
 * @license https://unlicense.org/UNLICENSE UNLICENSE
 *
 * _version_ $version
 */

declare(strict_types=1);

namespace Inane\Http;

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
enum HttpStatus {
        // 1xx Informational
    /**
     * 100
     */
    case Continue;
    /**
     * 101
     */
    case SwitchingProtocols;
    /**
     * 102
     */
    case Processing;

        // 2xx Success
    /**
     * 200
     */
    case Ok;
    /**
     * 201
     */
    case Created;
    /**
     * 202
     */
    case Accepted;
    /**
     * 203
     */
    case NonAuthoritativeInformation;
    /**
     * 204
     */
    case NoContent;
    /**
     * 205
     */
    case ResetContent;
    /**
     * 206
     */
    case PartialContent;
    /**
     * 207
     */
    case MultiStatus;
    /**
     * 208
     */
    case AlreadyReported;
    /**
     * 226
     */
    case ImUsed;

        // 3xx Redirection
    /**
     * 300
     */
    case MultipleChoices;
    /**
     * 301
     */
    case MovedPermanently;
    /**
     * 302
     */
    case Found;
    /**
     * 303
     */
    case SeeOther;
    /**
     * 304
     */
    case NotModified;
    /**
     * 305
     */
    case UseProxy;
    /**
     * 306
     */
    case SwitchProxy;
    /**
     * 307
     */
    case TemporaryRedirect;
    /**
     * 308
     */
    case PermanentRedirect;
    /**
     * 308
     */
    case ResumeIncomplete;

        // 4xx Client Error
    /**
     * 400
     */
    case BadRequest;
    /**
     * 401
     */
    case Unauthorized;
    /**
     * 402
     */
    case PaymentRequired;
    /**
     * 403
     */
    case Forbidden;
    /**
     * 404
     */
    case NotFound;
    /**
     * 405
     */
    case MethodNotAllowed;
    /**
     * 406
     */
    case NotAcceptable;
    /**
     * 407
     */
    case ProxyAuthenticationRequired;
    /**
     * 408
     */
    case RequestTimeout;
    /**
     * 409
     */
    case Conflict;
    /**
     * 410
     */
    case Gone;
    /**
     * 411
     */
    case LengthRequired;
    /**
     * 412
     */
    case PreconditionFailed;
    /**
     * 413
     */
    case RequestEntityTooLarge;
    /**
     * 414
     */
    case RequestUriTooLong;
    /**
     * 415
     */
    case UnsupportedMediaType;
    /**
     * 416
     */
    case RequestedRangeNotSatisfiable;
    /**
     * 417
     */
    case ExpectationFailed;
    /**
     * 418
     */
    case ImATeapot;
    /**
     * 421
     */
    case MisdirectedRequest;
    /**
     * 422
     */
    case UnprocessableEntity;
    /**
     * 423
     */
    case Locked;
    /**
     * 424
     */
    case FailedDependency;
	/**
	 * 425
	 */
	case TooEarly;
    /**
     * 426
     */
    case UpgradeRequired;
    /**
     * 428
     */
    case PreconditionRequired;
    /**
     * 429
     */
    case TooManyRequests;
    /**
     * 431
     */
    case RequestHeaderFieldsTooLarge;
    /**
     * 440
     */
    case LoginTimeout;
    /**
     * 444
     */
    case NoResponse;
    /**
     * 449
     */
    case RetryWith;
    /**
     * 450
     */
    case BlockedByWindowsParentalControls;
    /**
     * 451
     */
    case UnavailableForLegalReasons;
    /**
     * 451
     */
    case Redirect;
    /**
     * 494
     */
    case RequestHeaderTooLarge;
    /**
     * 495
     */
    case CertError;
    /**
     * 496
     */
    case NoCert;
    /**
     * 497
     */
    case HttpToHttps;
    /**
     * 498
     */
    case TokenExpiredInvalid;
    /**
     * 499
     */
    case ClientClosedRequest;
    /**
     * 499
     */
    case TokenRequired;

        // 5xx Server Error
    /**
     * 500
     */
    case InternalServerError;
    /**
     * 501
     */
    case NotImplemented;
    /**
     * 502
     */
    case BadGateway;
    /**
     * 503
     */
    case ServiceUnavailable;
    /**
     * 504
     */
    case GatewayTimeout;
    /**
     * 505
     */
    case HttpVersionNotSupported;
    /**
     * 506
     */
    case VariantAlsoNegotiates;
    /**
     * 507
     */
    case InsufficientStorage;
    /**
     * 508
     */
    case LoopDetected;
    /**
     * 509
     */
    case BandwidthLimitExceeded;
    /**
     * 510
     */
    case NotExtended;
    /**
     * 511
     */
    case NetworkAuthenticationRequired;
    /**
     * 520
     */
    case UnknownError;
    /**
     * 521
     */
    case WebServerIsDown;
    /**
     * 522
     */
    case ConnectionTimedOut;
    /**
     * 523
     */
    case OriginIsUnreachable;
    /**
     * 524
     */
    case ATimeoutOccurred;
    /**
     * 525
     */
    case SslHandshakeFailed;
    /**
     * 526
     */
    case InvalidSslCertificate;
    /**
     * 527
     */
    case RailgunError;

    /**
     * Get HTTP Status by Code
     *
     * @return static
     */
    public static function from(int $code): static {
        return match ($code) {
            100 => static::Continue,
            101 => static::SwitchingProtocols,
            102 => static::Processing,
            200 => static::Ok,
            201 => static::Created,
            202 => static::Accepted,
            203 => static::NonAuthoritativeInformation,
            204 => static::NoContent,
            205 => static::ResetContent,
            206 => static::PartialContent,
            207 => static::MultiStatus,
            208 => static::AlreadyReported,
            226 => static::ImUsed,
            300 => static::MultipleChoices,
            301 => static::MovedPermanently,
            302 => static::Found,
            303 => static::SeeOther,
            304 => static::NotModified,
            305 => static::UseProxy,
            306 => static::SwitchProxy,
            307 => static::TemporaryRedirect,
            308 => static::PermanentRedirect,
            308 => static::ResumeIncomplete,
            400 => static::BadRequest,
            401 => static::Unauthorized,
            402 => static::PaymentRequired,
            403 => static::Forbidden,
            404 => static::NotFound,
            405 => static::MethodNotAllowed,
            406 => static::NotAcceptable,
            407 => static::ProxyAuthenticationRequired,
            408 => static::RequestTimeout,
            409 => static::Conflict,
            410 => static::Gone,
            411 => static::LengthRequired,
            412 => static::PreconditionFailed,
            413 => static::RequestEntityTooLarge,
            414 => static::RequestUriTooLong,
            415 => static::UnsupportedMediaType,
            416 => static::RequestedRangeNotSatisfiable,
            417 => static::ExpectationFailed,
            418 => static::ImATeapot,
            421 => static::MisdirectedRequest,
            422 => static::UnprocessableEntity,
            423 => static::Locked,
            424 => static::FailedDependency,
            424 => static::TooEarly,
            426 => static::UpgradeRequired,
            428 => static::PreconditionRequired,
            429 => static::TooManyRequests,
            431 => static::RequestHeaderFieldsTooLarge,
            440 => static::LoginTimeout,
            444 => static::NoResponse,
            449 => static::RetryWith,
            450 => static::BlockedByWindowsParentalControls,
            451 => static::UnavailableForLegalReasons,
            451 => static::Redirect,
            494 => static::RequestHeaderTooLarge,
            495 => static::CertError,
            496 => static::NoCert,
            497 => static::HttpToHttps,
            498 => static::TokenExpiredInvalid,
            499 => static::ClientClosedRequest,
            499 => static::TokenRequired,
            500 => static::InternalServerError,
            501 => static::NotImplemented,
            502 => static::BadGateway,
            503 => static::ServiceUnavailable,
            504 => static::GatewayTimeout,
            505 => static::HttpVersionNotSupported,
            506 => static::VariantAlsoNegotiates,
            507 => static::InsufficientStorage,
            508 => static::LoopDetected,
            509 => static::BandwidthLimitExceeded,
            510 => static::NotExtended,
            511 => static::NetworkAuthenticationRequired,
            520 => static::UnknownError,
            521 => static::WebServerIsDown,
            522 => static::ConnectionTimedOut,
            523 => static::OriginIsUnreachable,
            524 => static::ATimeoutOccurred,
            525 => static::SslHandshakeFailed,
            526 => static::InvalidSslCertificate,
            527 => static::RailgunError,
            default => static::Ok,
        };
    }

    /**
     * Get HTTP Status Code
     *
     * @return int
     */
    public function code(): int {
        return match ($this) {
                // 1xx Informational
            static::Continue => 100,
            static::SwitchingProtocols => 101,
            static::Processing => 102,
                // 2xx Success
            static::Ok => 200,
            static::Created => 201,
            static::Accepted => 202,
            static::NonAuthoritativeInformation => 203,
            static::NoContent => 204,
            static::ResetContent => 205,
            static::PartialContent => 206,
            static::MultiStatus => 207,
            static::AlreadyReported => 208,
            static::ImUsed => 226,
                // 3xx Redirection
            static::MultipleChoices => 300,
            static::MovedPermanently => 301,
            static::Found => 302,
            static::SeeOther => 303,
            static::NotModified => 304,
            static::UseProxy => 305,
            static::SwitchProxy => 306,
            static::TemporaryRedirect => 307,
            static::PermanentRedirect, static::ResumeIncomplete => 308,
                // 4xx Client Error
            static::BadRequest => 400,
            static::Unauthorized => 401,
            static::PaymentRequired => 402,
            static::Forbidden => 403,
            static::NotFound => 404,
            static::MethodNotAllowed => 405,
            static::NotAcceptable => 406,
            static::ProxyAuthenticationRequired => 407,
            static::RequestTimeout => 408,
            static::Conflict => 409,
            static::Gone => 410,
            static::LengthRequired => 411,
            static::PreconditionFailed => 412,
            static::RequestEntityTooLarge => 413,
            static::RequestUriTooLong => 414,
            static::UnsupportedMediaType => 415,
            static::RequestedRangeNotSatisfiable => 416,
            static::ExpectationFailed => 417,
            static::ImATeapot => 418,
            static::MisdirectedRequest => 421,
            static::UnprocessableEntity => 422,
            static::Locked => 423,
            static::FailedDependency => 424,
            static::TooEarly => 425,
            static::UpgradeRequired => 426,
            static::PreconditionRequired => 428,
            static::TooManyRequests => 429,
            static::RequestHeaderFieldsTooLarge => 431,
            static::LoginTimeout => 440,
            static::NoResponse => 444,
            static::RetryWith => 449,
            static::BlockedByWindowsParentalControls => 450,
            static::UnavailableForLegalReasons, static::Redirect => 451,
            static::RequestHeaderTooLarge => 494,
            static::CertError => 495,
            static::NoCert => 496,
            static::HttpToHttps => 497,
            static::TokenExpiredInvalid => 498,
            static::ClientClosedRequest, static::TokenRequired => 499,
                // 5xx Server Error
            static::InternalServerError => 500,
            static::NotImplemented => 501,
            static::BadGateway => 502,
            static::ServiceUnavailable => 503,
            static::GatewayTimeout => 504,
            static::HttpVersionNotSupported => 505,
            static::VariantAlsoNegotiates => 506,
            static::InsufficientStorage => 507,
            static::LoopDetected => 508,
            static::BandwidthLimitExceeded => 509,
            static::NotExtended => 510,
            static::NetworkAuthenticationRequired => 511,
            static::UnknownError => 520,
            static::WebServerIsDown => 521,
            static::ConnectionTimedOut => 522,
            static::OriginIsUnreachable => 523,
            static::ATimeoutOccurred => 524,
            static::SslHandshakeFailed => 525,
            static::InvalidSslCertificate => 526,
            static::RailgunError => 527,
            default => 0,
        };
    }

    /**
     * Get HTTP Status Description
     *
     * @return string
     */
    public function description(): string {
        return match ($this) {
                // 1xx Informational
            static::Continue => 'The server has received the request headers, and that the client should proceed to send the request body.',
            static::SwitchingProtocols => 'The requester has asked the server to switch protocols and the server is acknowledging that it will do so.',
            static::Processing => 'The server has received and is processing the request, but no response is available yet.',
                // 2xx Success
            static::Ok => 'The standard response for successful HTTP requests.',
            static::Created => 'The request has been fulfilled and a new resource has been created.',
            static::Accepted => 'The request has been accepted but has not been processed yet. This code does not guarantee that the request will process successfully.',
            static::NonAuthoritativeInformation => 'HTTP 1.1. The server successfully processed the request but is returning information from another source.',
            static::NoContent => 'The server accepted the request but is not returning any content. This is often used as a response to a DELETE request.',
            static::ResetContent => 'Similar to a 204 No Content response but this response requires the requester to reset the document view.',
            static::PartialContent => 'The server is delivering only a portion of the content, as requested by the client via a range header.',
            static::MultiStatus => 'The message body that follows is an XML message and can contain a number of separate response codes, depending on how many sub-requests were made.',
            static::AlreadyReported => 'The members of a DAV binding have already been enumerated in a previous reply to this request, and are not being included again.',
            static::ImUsed => 'The server has fulfilled a GET request for the resource, and the response is a representation of the result of one or more instance-manipulations applied to the current instance.',
                // 3xx Redirection
            static::MultipleChoices => 'There are multiple options that the client may follow.',
            static::MovedPermanently => 'The resource has been moved and all further requests should reference its new URI.',
            static::Found => 'The HTTP 1.0 specification described this status as "Moved Temporarily", but popular browsers respond to this status similar to behavior intended for 303. The resource can be retrieved by referencing the returned URI.',
            static::SeeOther => 'The resource can be retrieved by following other URI using the GET method. When received in response to a POST, PUT, or DELETE, it can usually be assumed that the server processed the request successfully and is sending the client to an informational endpoint.',
            static::NotModified => 'The resource has not been modified since the version specified in If-Modified-Since or If-Match headers. The resource will not be returned in response body.',
            static::UseProxy => 'HTTP 1.1. The resource is only available through a proxy and the address is provided in the response.',
            static::SwitchProxy => 'Deprecated in HTTP 1.1. Used to mean that subsequent requests should be sent using the specified proxy.',
            static::TemporaryRedirect => 'HTTP 1.1. The request should be repeated with the URI provided in the response, but future requests should still call the original URI.',
            static::PermanentRedirect => 'Experimental. The request and all future requests should be repeated with the URI provided in the response. The HTTP method is not allowed to be changed in the subsequent request.',
            static::ResumeIncomplete => 'This code is used in the Resumable HTTP Requests Proposal to resume aborted PUT or POST requests',
                // 4xx Client Error
            static::BadRequest => 'The request could not be fulfilled due to the incorrect syntax of the request.',
            static::Unauthorized => 'The requester is not authorized to access the resource. This is similar to 403 but is used in cases where authentication is expected but has failed or has not been provided.',
            static::PaymentRequired => 'Reserved for future use. Some web services use this as an indication that the client has sent an excessive number of requests.',
            static::Forbidden => 'The request was formatted correctly but the server is refusing to supply the requested resource. Unlike 401, authenticating will not make a difference in the server\'s response.',
            static::NotFound => 'The resource could not be found. This is often used as a catch-all for all invalid URIs requested of the server.',
            static::MethodNotAllowed => 'The resource was requested using a method that is not allowed. For example, requesting a resource via a POST method when the resource only supports the GET method.',
            static::NotAcceptable => 'The resource is valid, but cannot be provided in a format specified in the Accept headers in the request.',
            static::ProxyAuthenticationRequired => 'Authentication is required with the proxy before requests can be fulfilled.',
            static::RequestTimeout => 'The server timed out waiting for a request from the client. The client is allowed to repeat the request.',
            static::Conflict => 'The request cannot be completed due to a conflict in the request parameters.',
            static::Gone => 'The resource is no longer available at the requested URI and no redirection will be given.',
            static::LengthRequired => 'The request did not specify the length of its content as required by the resource.',
            static::PreconditionFailed => 'The server does not meet one of the preconditions specified by the client.',
            static::RequestEntityTooLarge => 'The request is larger than what the server is able to process.',
            static::RequestUriTooLong => 'The URI provided in the request is too long for the server to process. This is often used when too much data has been encoded into the URI of a GET request and a POST request should be used instead.',
            static::UnsupportedMediaType => 'The client provided data with a media type that the server does not support.',
            static::RequestedRangeNotSatisfiable => 'The client has asked for a portion of the resource but the server cannot supply that portion.',
            static::ExpectationFailed => 'The server cannot meet the requirements of the Expect request-header field.',
            static::ImATeapot => 'Any attempt to brew coffee with a teapot should result in the error code "418 I\'m a teapot". The resulting entity body MAY be short and stout.',
            static::MisdirectedRequest => 'The request was directed at a server that is not able to produce a response. This can be sent by a server that is not configured to produce responses for the combination of scheme and authority that are included in the request URI.',
            static::UnprocessableEntity => 'The request was formatted correctly but cannot be processed in its current form. Often used when the specified parameters fail validation errors.',
            static::Locked => 'The requested resource was found but has been locked and will not be returned.',
            static::FailedDependency => 'The request failed due to a failure of a previous request.',
            static::TooEarly => 'Indicates that the server is unwilling to risk processing a request that might be replayed.',
            static::UpgradeRequired => 'The client should repeat the request using an upgraded protocol such as TLS 1.0.',
            static::PreconditionRequired => 'The origin server requires the request to be conditional.',
            static::TooManyRequests => 'The user has sent too many requests in a given amount of time ("rate limiting").',
            static::RequestHeaderFieldsTooLarge => 'The server is unwilling to process the request because its header fields are too large.',
            static::LoginTimeout => 'A Microsoft extension. Indicates that your session has expired.',
            static::NoResponse => 'Used in Nginx logs to indicate that the server has returned no information to the client and closed the connection (useful as a deterrent for malware).',
            static::RetryWith => 'A Microsoft extension. The request should be retried after performing the appropriate action.',
            static::BlockedByWindowsParentalControls => 'A Microsoft extension. This error is given when Windows Parental Controls are turned on and are blocking access to the given webpage.',
            static::UnavailableForLegalReasons => 'A server operator has received a legal demand to deny access to a resource or to a set of resources that includes the requested resource.',
            static::Redirect => 'Used in Exchange ActiveSync if there either is a more efficient server to use or the server cannot access the users\' mailbox.',
            static::RequestHeaderTooLarge => 'Nginx internal code similar to 431 but it was introduced earlier in version 0.9.4 (on January 21, 2011).',
            static::CertError => 'Nginx internal code used when SSL client certificate error occurred to distinguish it from 4XX in a log and an error page redirection.',
            static::NoCert => 'Nginx internal code used when client didn\'t provide certificate to distinguish it from 4XX in a log and an error page redirection.',
            static::HttpToHttps => 'Nginx internal code used for the plain HTTP requests that are sent to HTTPS port to distinguish it from 4XX in a log and an error page redirection.',
            static::TokenExpiredInvalid => 'Returned by ArcGIS for Server. A code of 498 indicates an expired or otherwise invalid token.',
            static::ClientClosedRequest => 'Used in Nginx logs to indicate when the connection has been closed by client while the server is still processing its request, making server unable to send a status code back.',
            static::TokenRequired => 'Returned by ArcGIS for Server. A code of 499 indicates that a token is required (if no token was submitted).',
                // 5xx Server Error
            static::InternalServerError => 'A generic status for an error in the server itself.',
            static::NotImplemented => 'The server cannot respond to the request. This usually implies that the server could possibly support the request in the future — otherwise a 4xx status may be more appropriate.',
            static::BadGateway => 'The server is acting as a proxy and did not receive an acceptable response from the upstream server.',
            static::ServiceUnavailable => 'The server is down and is not accepting requests.',
            static::GatewayTimeout => 'The server is acting as a proxy and did not receive a response from the upstream server.',
            static::HttpVersionNotSupported => 'The server does not support the HTTP protocol version specified in the request.',
            static::VariantAlsoNegotiates => 'Transparent content negotiation for the request results in a circular reference.',
            static::InsufficientStorage => 'The user or server does not have sufficient storage quota to fulfil the request.',
            static::LoopDetected => 'The server detected an infinite loop in the request.',
            static::BandwidthLimitExceeded => 'This status code is not specified in any RFCs. Its use is unknown.',
            static::NotExtended => 'Further extensions to the request are necessary for it to be fulfilled.',
            static::NetworkAuthenticationRequired => 'The client must authenticate with the network before sending requests.',
            static::UnknownError => 'This status code is not specified in any RFC and is returned by certain services, for instance Microsoft Azure and CloudFlare servers: "The 520 error is essentially a "catch-all" response for when the origin server returns something unexpected or something that is not tolerated/interpreted (protocol violation or empty response)."',
            static::WebServerIsDown => 'The origin server has refused the connection from CloudFlare.',
            static::ConnectionTimedOut => 'CloudFlare could not negotiate a TCP handshake with the origin server.',
            static::OriginIsUnreachable => 'CloudFlare could not reach the origin server; for example, if the DNS records for the origin server are incorrect.',
            static::ATimeoutOccurred => 'CloudFlare was able to complete a TCP connection to the origin server, but did not receive a timely HTTP response.',
            static::SslHandshakeFailed => 'CloudFlare could not negotiate a SSL/TLS handshake with the origin server.',
            static::InvalidSslCertificate => 'CloudFlare could not validate the SSL/TLS certificate that the origin server presented.',
            static::RailgunError => 'The request timed out or failed after the WAN connection has been established.',
            default => 'UNKNOWN!',
        };
    }

    /**
     * Get HTTP Status Message
     *
     * @return string
     */
    public function message(): string {
        return match ($this) {
                // 1xx Informational
            static::Continue => 'Continue',
            static::SwitchingProtocols => 'Switching protocols',
            static::Processing => 'Processing',
                // 2xx Success
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
                // 3xx Redirection
            static::MultipleChoices => 'Multiple choices',
            static::MovedPermanently => 'Moved permanently',
            static::Found => 'Found',
            static::SeeOther => 'See other',
            static::NotModified => 'Not modified',
            static::UseProxy => 'Use proxy',
            static::SwitchProxy => 'Switch proxy',
            static::TemporaryRedirect => 'Temporary redirect',
            static::PermanentRedirect => 'Permanent redirect',
            static::ResumeIncomplete => 'Resume incomplete',
                // 4xx Client Error
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
            static::TooEarly => 'Too early',
            static::UpgradeRequired => 'Upgrade required',
            static::PreconditionRequired => 'Precondition required',
            static::TooManyRequests => 'Too many requests',
            static::RequestHeaderFieldsTooLarge => 'Request header fields too large',
            static::LoginTimeout => 'Login timeout',
            static::NoResponse => 'No response',
            static::RetryWith => 'Retry with',
            static::BlockedByWindowsParentalControls => 'Blocked by windows parental controls',
            static::UnavailableForLegalReasons => 'Unavailable for legal reasons',
            static::Redirect => 'Redirect',
            static::RequestHeaderTooLarge => 'Request header too large',
            static::CertError => 'Cert error',
            static::NoCert => 'No cert',
            static::HttpToHttps => 'Http to https',
            static::TokenExpiredInvalid => 'Token expired invalid',
            static::ClientClosedRequest => 'Client closed request',
            static::TokenRequired => 'Token required',
                // 5xx Server Error
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
            default => 'UNKNOWN!',
        };
    }
}
