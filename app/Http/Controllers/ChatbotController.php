<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use OpenAI\Laravel\Facades\OpenAI;

class ChatbotController extends Controller
{
    public function ask(Request $request)
    {
        $request->validate([
            'message' => 'required|string',
        ]);

        $userMessage = $request->input('message');

        try {
            $result = OpenAI::chat()->create([
                'model' => 'gpt-5-nano',
                'messages' => [
                    ['role' => 'system', 'content' => "You are an AI assistant built into a 3D Maps Viewer. 
                    You have access to specific map controls via tools/functions. 
                    When a user asks to see something on the map (like highest point, flood risks, POIs), you MUST call the appropriate function instead of just typing a description.
                    If they ask a general question, you can reply normally. Keep replies extremely short (1-2 sentences)."],
                    ['role' => 'user', 'content' => $userMessage],
                ],
                'tools' => [
                    [
                        'type' => 'function',
                        'function' => [
                            'name' => 'showHighestPoint',
                            'description' => 'Highlights the highest elevation point on the 3D model.',
                        ]
                    ],
                    [
                        'type' => 'function',
                        'function' => [
                            'name' => 'showFloodRisk',
                            'description' => 'Displays colored flood risk zones overlaid on the 3D model.',
                            'parameters' => [
                                'type' => 'object',
                                'properties' => [
                                    'level' => [
                                        'type' => 'string',
                                        'enum' => ['low', 'moderate', 'high'],
                                        'description' => 'The specific flood risk severity level to display.',
                                    ],
                                ],
                                'required' => ['level'],
                            ]
                        ]
                    ],
                    [
                        'type' => 'function',
                        'function' => [
                            'name' => 'showPOIs',
                            'description' => 'Shows important points of interest (Schools, Hospitals, etc) on the map.',
                        ]
                    ],
                    [
                        'type' => 'function',
                        'function' => [
                            'name' => 'resetCamera',
                            'description' => 'Resets the camera back to the original default view.',
                        ]
                    ],
                ],
            ]);

            $responseMessage = $result->choices[0]->message;

            // Check if the AI decided to call one of our ViewerAPI functions
            if (isset($responseMessage->toolCalls) && count($responseMessage->toolCalls) > 0) {
                $toolCall = $responseMessage->toolCalls[0];
                $functionName = $toolCall->function->name;
                $functionArgs = json_decode($toolCall->function->arguments, true) ?? [];

                return response()->json([
                    'reply' => "I am updating the map for you now!",
                    'action' => $functionName,
                    'action_args' => $functionArgs,
                ]);
            }

            // Otherwise, it just replied with normal text
            return response()->json([
                'reply' => $responseMessage->content ?? 'I could not process that request.',
                'action' => null,
                'action_args' => null,
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'reply' => 'Error: ' . $e->getMessage(),
                'action' => null,
                'action_args' => null,
            ], 500);
        }
    }
}
