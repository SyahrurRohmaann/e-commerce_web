export function ErrorState({ message = 'Something went wrong.', onRetry }) {
  return <div role="alert" className="border border-red-200 bg-red-50 p-6 text-center text-red-800">
    <p>{message}</p>
    {onRetry && <button type="button" onClick={onRetry} className="mt-4 underline font-bold">Try again</button>}
  </div>;
}

export default ErrorState;
