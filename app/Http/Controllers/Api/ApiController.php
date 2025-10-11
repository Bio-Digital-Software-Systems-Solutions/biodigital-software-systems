<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use OpenApi\Attributes as OA;

/**
 * Base API Controller with OpenAPI documentation
 *
 * @package App\Http\Controllers\Api
 */
#[OA\Info(
    version: "1.0.0",
    title: "AIG-App API",
    description: "Comprehensive API documentation for AIG-App - Organizational Management Platform.
    This API provides endpoints for managing events, books, articles, chat, and users.",
    contact: new OA\Contact(
        name: "API Support",
        email: "support@aig-app.com"
    )
)]
#[OA\Server(
    url: "http://localhost:8000",
    description: "Local Development Server"
)]
#[OA\Server(
    url: "https://api.aig-app.com",
    description: "Production Server"
)]
#[OA\SecurityScheme(
    securityScheme: "sanctum",
    type: "http",
    description: "Laravel Sanctum authentication",
    name: "Authorization",
    in: "header",
    scheme: "bearer",
    bearerFormat: "JWT"
)]
#[OA\Tag(
    name: "Authentication",
    description: "Authentication and authorization endpoints"
)]
#[OA\Tag(
    name: "Events",
    description: "Event management endpoints"
)]
#[OA\Tag(
    name: "Books",
    description: "Book library and rental management"
)]
#[OA\Tag(
    name: "Articles",
    description: "Article creation and management"
)]
#[OA\Tag(
    name: "Chat",
    description: "Real-time messaging endpoints"
)]
#[OA\Tag(
    name: "Users",
    description: "User management endpoints"
)]
#[OA\Tag(
    name: "Health",
    description: "Application health check endpoints"
)]
class ApiController extends Controller
{
    /**
     * Base API Controller
     *
     * This controller serves as the base for all API controllers
     * and contains the main OpenAPI documentation configuration.
     */
}
