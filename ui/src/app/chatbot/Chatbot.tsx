import { useEffect, useRef, useState } from 'react';
import { Link } from 'react-router-dom';
import { Bot, ExternalLink, Loader2, Send, Sparkles, Trash2, User, X } from 'lucide-react';

import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { cn } from '@/lib/utils';
import { answerQuestion, SUGGESTED_QUESTIONS, WELCOME_MESSAGE, type ChatbotReply } from './responder';
import { fetchAfrodukaPages, searchAfroduka } from './api';

/**
 * The public landing-page chatbot.
 *
 * Read-only and unauthenticated, so it can be dropped on any marketing page. It never
 * touches the dashboard or business data; the only outbound calls are the two proxy
 * routes the responder uses. Mounted once (HomeLayout) so it cannot be duplicated by a
 * page that also renders it.
 */

type ChatLink = { label: string; href: string };
type ChatSource = { title: string; url: string };

type Message = {
  id: number;
  role: 'user' | 'assistant';
  text: string;
  links: ChatLink[];
  sources: ChatSource[];
  at: Date;
  error?: boolean;
};

/** The responder's dependencies, resolved once. Injected so tests can replace them. */
const deps = { fetchPages: fetchAfrodukaPages, search: searchAfroduka };

/** Monotonic id for message keys; a module counter keeps render free of ref reads. */
let messageSeq = 0;
const nextId = (): number => ++messageSeq;

const clock = (date: Date): string => date.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });

/** Internal routes render through the router; anything absolute opens in a new tab. */
const RefLink = ({ href, className, children }: { href: string; className?: string; children: React.ReactNode }) => {
  if (href.startsWith('http')) {
    return (
      <a href={href} target='_blank' rel='noreferrer' className={className}>
        {children}
      </a>
    );
  }

  return (
    <Link to={href} className={className}>
      {children}
    </Link>
  );
};

export const Chatbot = () => {
  const makeMessage = (role: Message['role'], text: string, extra: Partial<Message> = {}): Message => ({
    id: nextId(),
    role,
    text,
    links: [],
    sources: [],
    at: new Date(),
    ...extra,
  });

  const [open, setOpen] = useState(false);
  const [input, setInput] = useState('');
  const [pending, setPending] = useState(false);
  const [messages, setMessages] = useState<Message[]>(() => [makeMessage('assistant', WELCOME_MESSAGE)]);

  const endRef = useRef<HTMLDivElement>(null);
  const inputRef = useRef<HTMLInputElement>(null);

  useEffect(() => {
    if (open) inputRef.current?.focus();
  }, [open]);

  useEffect(() => {
    endRef.current?.scrollIntoView({ behavior: 'smooth', block: 'end' });
  }, [messages, pending, open]);

  const send = async (raw: string) => {
    const text = raw.trim();
    if (!text || pending) return;

    setInput('');
    setMessages((prev) => [...prev, makeMessage('user', text)]);
    setPending(true);

    try {
      const reply: ChatbotReply = await answerQuestion(text, deps);
      setMessages((prev) => [
        ...prev,
        makeMessage('assistant', reply.text, { links: reply.links, sources: reply.sources }),
      ]);
    } catch {
      setMessages((prev) => [
        ...prev,
        makeMessage('assistant', 'Sorry, something went wrong answering that. Please try again.', { error: true }),
      ]);
    } finally {
      setPending(false);
    }
  };

  const clear = () => {
    setMessages([makeMessage('assistant', WELCOME_MESSAGE)]);
    setInput('');
  };

  const latestAssistant = [...messages].reverse().find((message) => message.role === 'assistant')?.text ?? '';
  const showSuggestions = messages.length === 1 && !pending;

  return (
    <>
      <Button
        type='button'
        size='icon'
        onClick={() => setOpen((value) => !value)}
        aria-expanded={open}
        aria-controls='afroduka-chatbot-panel'
        aria-label={open ? 'Close AfroDuuka assistant' : 'Open AfroDuuka assistant'}
        className='fixed bottom-5 right-5 z-50 h-14 w-14 rounded-full shadow-lg'
      >
        {open ? <X className='h-6 w-6' /> : <Sparkles className='h-6 w-6' />}
      </Button>

      {open && (
        <section
          id='afroduka-chatbot-panel'
          role='dialog'
          aria-label='AfroDuuka assistant'
          className='fixed inset-x-3 bottom-24 z-50 flex h-[min(70vh,34rem)] flex-col overflow-hidden rounded-2xl border border-border/70 bg-card shadow-2xl sm:inset-x-auto sm:right-5 sm:w-[24rem]'
        >
          <header className='flex items-center gap-3 border-b border-border/70 bg-linear-to-r from-primary/5 to-primary/10 px-4 py-3'>
            <div className='flex h-9 w-9 items-center justify-center rounded-full bg-primary/10'>
              <Bot className='h-5 w-5 text-primary' />
            </div>
            <div className='min-w-0 flex-1'>
              <p className='truncate text-sm font-semibold text-foreground'>AfroDuuka Assistant</p>
              <p className='truncate text-xs text-muted-foreground'>Features, pricing and getting started</p>
            </div>
            <Button
              type='button'
              variant='ghost'
              size='icon'
              onClick={clear}
              aria-label='Clear conversation'
              className='h-8 w-8 shrink-0 text-muted-foreground'
            >
              <Trash2 className='h-4 w-4' />
            </Button>
            <Button
              type='button'
              variant='ghost'
              size='icon'
              onClick={() => setOpen(false)}
              aria-label='Minimize chat'
              className='h-8 w-8 shrink-0 text-muted-foreground'
            >
              <X className='h-4 w-4' />
            </Button>
          </header>

          <div role='log' aria-live='polite' className='flex-1 space-y-4 overflow-y-auto p-4 scrollbar-thin'>
            <span className='sr-only' role='status'>
              {pending ? 'The assistant is thinking…' : latestAssistant}
            </span>

            {messages.map((message) => (
              <div
                key={message.id}
                className={cn('flex gap-2', message.role === 'user' ? 'justify-end' : 'justify-start')}
              >
                {message.role === 'assistant' && (
                  <div className='mt-0.5 flex h-7 w-7 shrink-0 items-center justify-center rounded-full bg-primary/10 text-primary'>
                    <Bot className='h-4 w-4' />
                  </div>
                )}

                <div className='max-w-[80%] space-y-1'>
                  <div
                    className={cn(
                      'whitespace-pre-wrap rounded-2xl px-3.5 py-2 text-sm leading-relaxed shadow-sm',
                      message.role === 'user'
                        ? 'rounded-tr-md bg-primary text-primary-foreground'
                        : 'rounded-tl-md border border-border/60 bg-muted/70 text-foreground',
                      message.error && 'border-destructive/40 bg-destructive/5 text-destructive',
                    )}
                  >
                    {message.text}
                  </div>

                  {(message.links.length > 0 || message.sources.length > 0) && (
                    <div className='flex flex-wrap gap-1.5 pt-0.5'>
                      {message.links.map((link) => (
                        <RefLink
                          key={link.href}
                          href={link.href}
                          className='inline-flex items-center gap-1 rounded-full border border-border/70 bg-background px-2.5 py-1 text-xs font-medium text-primary hover:bg-primary/5'
                        >
                          {link.label}
                        </RefLink>
                      ))}
                      {message.sources.map((source) => (
                        <RefLink
                          key={source.url}
                          href={source.url}
                          className='inline-flex items-center gap-1 rounded-full border border-border/70 bg-background px-2.5 py-1 text-xs text-muted-foreground hover:bg-muted'
                        >
                          <ExternalLink className='h-3 w-3' />
                          {source.title}
                        </RefLink>
                      ))}
                    </div>
                  )}

                  <p
                    className={cn(
                      'px-1 text-[10px] text-muted-foreground',
                      message.role === 'user' ? 'text-right' : 'text-left',
                    )}
                  >
                    {clock(message.at)}
                  </p>
                </div>

                {message.role === 'user' && (
                  <div className='mt-0.5 flex h-7 w-7 shrink-0 items-center justify-center rounded-full bg-secondary/30 text-secondary'>
                    <User className='h-4 w-4' />
                  </div>
                )}
              </div>
            ))}

            {pending && (
              <div className='flex gap-2'>
                <div className='mt-0.5 flex h-7 w-7 shrink-0 items-center justify-center rounded-full bg-primary/10 text-primary'>
                  <Bot className='h-4 w-4' />
                </div>
                <div className='flex items-center gap-2 rounded-2xl rounded-tl-md border border-border/60 bg-muted/70 px-3.5 py-2 text-sm text-muted-foreground'>
                  <Loader2 className='h-3.5 w-3.5 animate-spin' />
                  Looking that up…
                </div>
              </div>
            )}

            {showSuggestions && (
              <div className='flex flex-wrap gap-1.5 pl-9'>
                {SUGGESTED_QUESTIONS.map((question) => (
                  <button
                    key={question}
                    type='button'
                    onClick={() => void send(question)}
                    className='rounded-full border border-primary/30 bg-primary/5 px-2.5 py-1 text-xs font-medium text-primary transition-colors hover:bg-primary/10'
                  >
                    {question}
                  </button>
                ))}
              </div>
            )}

            <div ref={endRef} />
          </div>

          <form
            onSubmit={(event) => {
              event.preventDefault();
              void send(input);
            }}
            className='flex gap-2 border-t border-border/70 bg-muted/20 p-3'
          >
            <Input
              ref={inputRef}
              value={input}
              onChange={(event) => setInput(event.target.value)}
              placeholder='Ask about AfroDuuka…'
              aria-label='Message to the AfroDuuka assistant'
              disabled={pending}
              className='flex-1 bg-background'
            />
            <Button
              type='submit'
              size='icon'
              disabled={!input.trim() || pending}
              aria-label={pending ? 'Sending message' : 'Send message'}
              className='shrink-0'
            >
              {pending ? <Loader2 className='h-4 w-4 animate-spin' /> : <Send className='h-4 w-4' />}
            </Button>
          </form>
        </section>
      )}
    </>
  );
};
