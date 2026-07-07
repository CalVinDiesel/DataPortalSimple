import React, { useState, useRef, useEffect } from 'react';
import { MessageSquare, X, Send, Bot, User } from 'lucide-react';

import axios from 'axios';

export default function ChatbotWidget() {
    const [isOpen, setIsOpen] = useState(false);
    const [messages, setMessages] = useState<{ role: 'user' | 'bot', content: string }[]>([
        { role: 'bot', content: 'Hello! I am your 3D spatial assistant. How can I help you navigate or analyze this model?' }
    ]);
    const [input, setInput] = useState('');
    const [isTyping, setIsTyping] = useState(false);
    const messagesEndRef = useRef<HTMLDivElement>(null);

    const scrollToBottom = () => {
        messagesEndRef.current?.scrollIntoView({ behavior: "smooth" });
    };

    useEffect(() => {
        scrollToBottom();
    }, [messages, isTyping]);

    const handleSend = async () => {
        if (!input.trim()) return;

        const userMsg = input.trim();
        setMessages(prev => [...prev, { role: 'user', content: userMsg }]);
        setInput('');
        setIsTyping(true);

        try {
            // Send message to our new Laravel Controller (Part 3)
            const response = await axios.post('/user/chat', { message: userMsg });
            const { reply, action, action_args } = response.data;
            
            // Add the AI's text response to the chat window
            setMessages(prev => [...prev, { role: 'bot', content: reply }]);

            // If the AI decided it needs to control the map, trigger the Bridge API!
            if (action && (window as any).ViewerAPI) {
                const api = (window as any).ViewerAPI;
                
                if (action === 'showHighestPoint' && api.showHighestPoint) {
                    api.showHighestPoint();
                } else if (action === 'showFloodRisk' && api.showFloodRisk) {
                    api.showFloodRisk(action_args?.level || 'moderate');
                } else if (action === 'showPOIs' && api.showPOIs) {
                    api.showPOIs();
                } else if (action === 'resetCamera' && api.resetCamera) {
                    api.resetCamera();
                }
            }
        } catch (error) {
            console.error("AI Error:", error);
            setMessages(prev => [...prev, { role: 'bot', content: "Error connecting to AI. Did you add the API key?" }]);
        } finally {
            setIsTyping(false);
        }
    };

    const handleKeyPress = (e: React.KeyboardEvent) => {
        if (e.key === 'Enter') handleSend();
    };

    return (
        <div style={{
            position: 'fixed',
            bottom: '30px',
            right: '30px',
            zIndex: 9999,
            display: 'flex',
            flexDirection: 'column',
            alignItems: 'flex-end',
            fontFamily: 'Inter, system-ui, Avenir, Helvetica, Arial, sans-serif'
        }}>
            {/* Chat Window */}
            {isOpen && (
                <div style={{
                    width: '350px',
                    height: '500px',
                    backgroundColor: '#ffffff',
                    borderRadius: '12px',
                    border: '1px solid #e5e7eb',
                    boxShadow: '0 10px 30px rgba(0, 0, 0, 0.15)',
                    display: 'flex',
                    flexDirection: 'column',
                    marginBottom: '20px',
                    overflow: 'hidden',
                    animation: 'slideUp 0.3s ease-out'
                }}>
                    {/* Header */}
                    <div style={{
                        padding: '16px 20px',
                        background: '#ffffff',
                        borderBottom: '1px solid #f1f5f9',
                        display: 'flex',
                        justifyContent: 'space-between',
                        alignItems: 'center'
                    }}>
                        <div style={{ display: 'flex', alignItems: 'center', gap: '10px' }}>
                            <div style={{ background: '#eff6ff', padding: '8px', borderRadius: '10px' }}>
                                <Bot size={20} color="#2563eb" />
                            </div>
                            <h3 style={{ margin: 0, color: '#1e293b', fontSize: '1rem', fontWeight: 600 }}>AI Assistant</h3>
                        </div>
                        <button 
                            onClick={() => setIsOpen(false)}
                            style={{ background: 'transparent', border: 'none', cursor: 'pointer', color: '#94a3b8' }}
                        >
                            <X size={20} />
                        </button>
                    </div>

                    {/* Messages Area */}
                    <div style={{
                        flex: 1,
                        padding: '20px',
                        overflowY: 'auto',
                        display: 'flex',
                        flexDirection: 'column',
                        gap: '16px',
                        background: '#f8fafc'
                    }}>
                        {messages.map((msg, idx) => (
                            <div key={idx} style={{
                                display: 'flex',
                                alignItems: 'flex-start',
                                gap: '12px',
                                alignSelf: msg.role === 'user' ? 'flex-end' : 'flex-start',
                                maxWidth: '85%'
                            }}>
                                {msg.role === 'bot' && (
                                    <div style={{ background: '#ffffff', padding: '8px', borderRadius: '50%', flexShrink: 0, boxShadow: '0 2px 4px rgba(0,0,0,0.05)' }}>
                                        <Bot size={16} color="#2563eb" />
                                    </div>
                                )}
                                <div style={{
                                    padding: '12px 16px',
                                    borderRadius: '12px',
                                    backgroundColor: msg.role === 'user' ? '#2563eb' : '#ffffff',
                                    color: msg.role === 'user' ? '#ffffff' : '#334155',
                                    fontSize: '0.9rem',
                                    lineHeight: '1.5',
                                    borderTopRightRadius: msg.role === 'user' ? '4px' : '12px',
                                    borderTopLeftRadius: msg.role === 'bot' ? '4px' : '12px',
                                    boxShadow: msg.role === 'bot' ? '0 2px 5px rgba(0,0,0,0.02)' : 'none',
                                    border: msg.role === 'bot' ? '1px solid #e2e8f0' : 'none'
                                }}>
                                    {msg.content}
                                </div>
                            </div>
                        ))}
                        {isTyping && (
                            <div style={{ display: 'flex', alignItems: 'center', gap: '8px', color: '#94a3b8', fontSize: '0.85rem' }}>
                                <Bot size={16} /> AI is thinking...
                            </div>
                        )}
                        <div ref={messagesEndRef} />
                    </div>

                    {/* Input Area */}
                    <div style={{
                        padding: '16px',
                        borderTop: '1px solid #f1f5f9',
                        background: '#ffffff'
                    }}>
                        <div style={{
                            display: 'flex',
                            background: '#f8fafc',
                            borderRadius: '24px',
                            padding: '6px 6px 6px 16px',
                            border: '1px solid #e2e8f0',
                            alignItems: 'center'
                        }}>
                            <input 
                                type="text" 
                                value={input}
                                onChange={(e) => setInput(e.target.value)}
                                onKeyPress={handleKeyPress}
                                placeholder="Ask a spatial question..."
                                style={{
                                    flex: 1,
                                    background: 'transparent',
                                    border: 'none',
                                    color: '#1e293b',
                                    outline: 'none',
                                    fontSize: '0.9rem'
                                }}
                            />
                            <button 
                                onClick={handleSend}
                                style={{
                                    background: '#2563eb',
                                    border: 'none',
                                    borderRadius: '50%',
                                    width: '36px',
                                    height: '36px',
                                    display: 'flex',
                                    alignItems: 'center',
                                    justifyContent: 'center',
                                    cursor: 'pointer',
                                    color: 'white',
                                    marginLeft: '8px',
                                    transition: 'transform 0.2s',
                                    boxShadow: '0 2px 4px rgba(37, 99, 235, 0.2)'
                                }}
                                onMouseOver={(e) => (e.currentTarget.style.transform = 'scale(1.05)')}
                                onMouseOut={(e) => (e.currentTarget.style.transform = 'scale(1)')}
                            >
                                <Send size={16} />
                            </button>
                        </div>
                    </div>
                </div>
            )}

            {/* Floating Action Button */}
            <button
                onClick={() => setIsOpen(!isOpen)}
                style={{
                    width: '56px',
                    height: '56px',
                    borderRadius: '50%',
                    background: '#ffffff',
                    border: '1px solid #e5e7eb',
                    cursor: 'pointer',
                    boxShadow: '0 8px 20px rgba(0, 0, 0, 0.1)',
                    display: 'flex',
                    justifyContent: 'center',
                    alignItems: 'center',
                    color: '#2563eb',
                    transition: 'all 0.3s cubic-bezier(0.175, 0.885, 0.32, 1.275)'
                }}
                onMouseOver={(e) => {
                    e.currentTarget.style.transform = 'scale(1.1)';
                    e.currentTarget.style.boxShadow = '0 12px 25px rgba(0, 0, 0, 0.15)';
                }}
                onMouseOut={(e) => {
                    e.currentTarget.style.transform = 'scale(1)';
                    e.currentTarget.style.boxShadow = '0 8px 20px rgba(0, 0, 0, 0.1)';
                }}
            >
                {isOpen ? <X size={26} color="#64748b" /> : <MessageSquare size={26} color="#2563eb" />}
            </button>
            
            <style>
                {`
                @keyframes slideUp {
                    from { opacity: 0; transform: translateY(20px); }
                    to { opacity: 1; transform: translateY(0); }
                }
                `}
            </style>
        </div>
    );
}
