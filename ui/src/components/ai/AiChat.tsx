import { useState, useEffect, useRef } from 'react';
import { Card, CardContent, CardFooter, CardHeader, CardTitle } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { useSendAiMessageMutation } from '@/app/store/features/ai/aiQuery';
import { Send, Bot, User, Loader2, Sparkles } from 'lucide-react';
import { cn } from '@/lib/utils';
import { useLoggedinUserQuery } from '@/app/store/features/auth/authQuery';

interface Message {
  role: 'user' | 'assistant';
  content: string;
  timestamp: Date;
}

export const AiChat = () => {
  const { data } = useLoggedinUserQuery();
  const user = data?.data?.username;
  const businessName = data?.data?.business?.name ?? 'your business';
  const [messages, setMessages] = useState<Message[]>([
    {
      role: 'assistant',
      content: `Hello! I\'m your AI inventory assistant. Ask me about your products, sales, stock levels, revenue, or any other business data.`,
      timestamp: new Date(),
    },
  ]);
  const [input, setInput] = useState('');
  const [sendMessage, { isLoading }] = useSendAiMessageMutation();
  const messagesEndRef = useRef<HTMLDivElement>(null);

  useEffect(() => {
    messagesEndRef.current?.scrollIntoView({ behavior: 'smooth' });
  }, [messages]);

  useEffect(() => {
    if (user) {
      setMessages((prev) => {
        const updated = [...prev];
        updated[0] = {
          role: 'assistant',
          content: `Hello ${user}! Welcome to ${businessName}. I'm your AI inventory assistant. Ask me about products, sales, stock levels, revenue, or any other business data.`,
          timestamp: new Date(),
        };
        return updated;
      });
    }
  }, [user, businessName]);

  const handleSend = async () => {
    const text = input.trim();
    if (!text || isLoading) return;

    setInput('');
    setMessages((prev) => [...prev, { role: 'user', content: text, timestamp: new Date() }]);

    try {
      const result = await sendMessage({ message: text }).unwrap();
      const response = result.success ? formatResponse(result.data) : result.error;
      setMessages((prev) => [...prev, { role: 'assistant', content: response, timestamp: new Date() }]);
    } catch {
      setMessages((prev) => [
        ...prev,
        { role: 'assistant', content: 'Sorry, something went wrong. Please try again.', timestamp: new Date() },
      ]);
    }
  };

  const handleKeyDown = (e: React.KeyboardEvent) => {
    if (e.key === 'Enter' && !e.shiftKey) {
      e.preventDefault();
      handleSend();
    }
  };

  return (
    <Card className='flex h-full flex-col overflow-hidden border-border/70 shadow-sm'>
      <CardHeader className='border-b border-border/70 bg-gradient-to-r from-primary/5 to-primary/10 px-5 py-4'>
        <CardTitle className='flex items-center gap-2 text-base'>
          <div className='flex h-6 w-6 items-center justify-center rounded-lg bg-primary/10'>
            <Sparkles className='h-3.5 w-3.5 text-primary' />
          </div>
          <span>AI Assistant</span>
          <span className='ml-auto flex h-2 w-2 rounded-full bg-emerald-500'>
            <span className='absolute inline-flex h-2 w-2 animate-ping rounded-full bg-emerald-400 opacity-75' />
          </span>
        </CardTitle>
      </CardHeader>
      <CardContent className='flex-1 space-y-4 overflow-y-auto p-5 scrollbar-thin'>
        {messages.map((msg, i) => (
          <div key={i} className={cn('flex gap-3', msg.role === 'user' ? 'justify-end' : 'justify-start')}>
            {msg.role === 'assistant' && (
              <div className='flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-gradient-to-br from-primary/20 to-primary/10 text-primary shadow-sm'>
                <Bot className='h-4 w-4' />
              </div>
            )}
            <div
              className={cn(
                'max-w-[85%] rounded-2xl px-4 py-2.5 text-sm leading-relaxed shadow-sm',
                msg.role === 'user'
                  ? 'bg-primary text-primary-foreground rounded-tr-md'
                  : 'bg-muted/70 text-foreground rounded-tl-md border border-border/50',
              )}
            >
              <span className='whitespace-pre-wrap'>{msg.content}</span>
            </div>
            {msg.role === 'user' && (
              <div className='flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-gradient-to-br from-secondary/30 to-secondary/10 text-secondary shadow-sm'>
                <User className='h-4 w-4' />
              </div>
            )}
          </div>
        ))}
        {isLoading && (
          <div className='flex gap-3'>
            <div className='flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-gradient-to-br from-primary/20 to-primary/10 text-primary shadow-sm'>
              <Bot className='h-4 w-4' />
            </div>
            <div className='flex items-center gap-2 rounded-2xl rounded-tl-md bg-muted/70 px-4 py-2.5 text-sm text-muted-foreground border border-border/50 shadow-sm'>
              <Loader2 className='h-3.5 w-3.5 animate-spin' />
              Thinking...
            </div>
          </div>
        )}
        <div ref={messagesEndRef} />
      </CardContent>
      <CardFooter className='border-t border-border/70 bg-muted/20 p-4'>
        <form
          onSubmit={(e) => {
            e.preventDefault();
            handleSend();
          }}
          className='flex w-full gap-2'
        >
          <Input
            value={input}
            onChange={(e) => setInput(e.target.value)}
            onKeyDown={handleKeyDown}
            placeholder='Ask about inventory, sales, stock...'
            disabled={isLoading}
            className='flex-1 bg-background'
          />
          <Button type='submit' size='icon' disabled={!input.trim() || isLoading} className='shrink-0 shadow-sm'>
            {isLoading ? <Loader2 className='h-4 w-4 animate-spin' /> : <Send className='h-4 w-4' />}
          </Button>
        </form>
      </CardFooter>
    </Card>
  );
};

function formatResponse(data: any): string {
  if (!data) return 'No data available.';

  const lines: string[] = [];

  if (data.message) return data.message;
  if (data.response) return data.response;

  if (data.products && Array.isArray(data.products)) {
    const items = data.products as any[];
    if (items.length === 0) return 'No products found.';
    items.forEach((p) => {
      const details = [`• ${p.name}${p.sku ? ` (SKU: ${p.sku})` : ''}`];
      if (p.quantity !== undefined) details.push(`  Stock: ${p.quantity}`);
      if (p.price !== undefined) details.push(`  Price: ${p.price}`);
      if (p.total_sold !== undefined) details.push(`  Sold: ${p.total_sold}`);
      if (p.profit_margin_percent !== undefined) details.push(`  Margin: ${p.profit_margin_percent}%`);
      lines.push(details.join('\n'));
    });
    if (data.total !== undefined) lines.push(`\nTotal: ${data.total} product(s)`);
  }

  if (data.payments && Array.isArray(data.payments)) {
    lines.push(`Pending payments: ${data.total_count ?? data.payments.length}`);
    if (data.total_pending_amount !== undefined) lines.push(`Total pending: ${data.total_pending_amount}`);
  }

  if (data.total_sales !== undefined && data.total_revenue !== undefined) {
    lines.push(`Sales: ${data.total_sales} transactions`);
    lines.push(`Revenue: ${data.total_revenue}`);
    if (data.average_order_value) lines.push(`Avg order value: ${data.average_order_value}`);
  }

  if (data.total_purchases !== undefined && data.total_spent !== undefined) {
    lines.push(`Purchases: ${data.total_purchases}`);
    lines.push(`Total spent: ${data.total_spent}`);
  }

  if (data.total_cost_value !== undefined) {
    lines.push(`Stock value (cost): ${data.total_cost_value}`);
    lines.push(`Stock value (retail): ${data.total_retail_value}`);
    if (data.potential_profit !== undefined) lines.push(`Potential profit: ${data.potential_profit}`);
  }

  if (data.gross_profit !== undefined) {
    lines.push(`Revenue: ${data.total_revenue}`);
    lines.push(`COGS: ${data.total_cost_of_goods}`);
    lines.push(`Gross Profit: ${data.gross_profit}`);
    if (data.profit_margin_percent !== undefined) lines.push(`Margin: ${data.profit_margin_percent}%`);
  }

  if (data.sales !== undefined && data.purchases !== undefined) {
    const s = data.sales;
    const p = data.purchases;
    lines.push(`Sales total: ${s.total ?? s} (${s.count ?? ''} transactions)`);
    lines.push(`Purchases total: ${p.total ?? p} (${p.count ?? ''} transactions)`);
    if (data.difference !== undefined) lines.push(`Difference: ${data.difference}`);
  }

  if (data.comparison) {
    if (data.changes?.sales_change_percent !== null) {
      lines.push(`Sales change: ${data.changes.sales_change_percent}%`);
    }
    if (data.changes?.purchases_change_percent !== null) {
      lines.push(`Purchases change: ${data.changes.purchases_change_percent}%`);
    }
  }

  if (data.summary) {
    lines.push(data.summary);
  }

  if (data.latest_sold_products && Array.isArray(data.latest_sold_products)) {
    const items = data.latest_sold_products as any[];
    items.forEach((p) => {
      lines.push(`• ${p.product} — Qty: ${p.quantity}, ${p.subtotal} (${p.sold_at})`);
    });
  }

  if (data.recent_purchases && Array.isArray(data.recent_purchases)) {
    const items = data.recent_purchases as any[];
    items.forEach((p) => {
      lines.push(`• #${p.id} — ${p.total_amount} (${p.status}) — ${p.date}`);
    });
  }

  if (data.customer_name) {
    lines.push(`Customer: ${data.customer_name}`);
    lines.push(`Total spent: ${data.total_spent}`);
  }

  if (data.supplier_name) {
    lines.push(`Supplier: ${data.supplier_name}`);
    lines.push(`Total spent: ${data.total_spent}`);
  }

  if (lines.length === 0) {
    return JSON.stringify(data, null, 2);
  }

  return lines.join('\n');
}
